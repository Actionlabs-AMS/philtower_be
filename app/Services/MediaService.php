<?php

namespace App\Services;

use App\Models\User;
use App\Models\MediaLibrary;
use App\Http\Resources\MediaResource;
use Illuminate\Support\Facades\Storage;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Filters\Frame\CustomFrameFilter;
use FFMpeg\Format\Video\Ogg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use Image;
use App\Helpers\S3Helper;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaService extends BaseService
{
	protected $useS3;

	public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new MediaResource(new MediaLibrary), new MediaLibrary());
      $this->useS3 = !empty(config('filesystems.disks.s3.bucket'));
  }
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10)
  {
    $allMedia = MediaLibrary::count();
    $perPage = (request('mode') == 'list') ? 10 : 24;

    $query = MediaLibrary::query()
      ->when(request('search'), function ($q) {
        return $q->search(request('search'));
      })
      ->when(request('type'), function ($q) {
        return $q->ofType(request('type'));
      })
      ->when(request('date'), function ($q) {
        return $q->byDate(request('date'));
      })
      ->when(request('order'), function ($q) {
        return $q->orderBy(request('order'), request('sort', 'asc'));
      })
      ->when(!request('order'), function ($q) {
        return $q->orderBy('id', 'desc');
      });

    return MediaResource::collection($query->paginate($perPage)->withQueryString())
      ->additional(['meta' => ['media_total' => $allMedia]]);
  }

  public function uploadFiles(array $files, User $user) 
  {
    $uploadedMedia = [];
    
    foreach($files as $file) {
      try {
        // Validate file
        if (!$file->isValid()) {
          throw new \Exception('Invalid file: ' . $file->getClientOriginalName());
        }

        $mime_type = explode("/", $file->getClientMimeType());
        $mediaType = $mime_type[0] ?? 'application';
        
        if ($this->useS3) {
          $data = $this->saveToS3($file, $user->id, $mediaType);
        } else {
          switch($mediaType) {
            case 'image':
              $data = $this->saveImage($file, $user->id);
              break;
            case 'video':
              $data = $this->saveVideo($file, $user->id);
              break;
            default:
              $data = $this->saveFile($file, $user->id);
          }
        }
        
        $media = MediaLibrary::create($data);
        $uploadedMedia[] = $media;
      } catch (\Exception $e) {
        \Log::error('Media upload error: ' . $e->getMessage());
        // Continue with other files
        continue;
      }
    }
    
    if (empty($uploadedMedia)) {
      throw new \Exception('No files were uploaded successfully.');
    }
    
    // Return the last uploaded media (for backward compatibility)
    // In future, we can return collection of all uploaded media
    return new MediaResource(end($uploadedMedia));
  }

  /**
   * Upload files and return all uploaded media (e.g. for ticket request attachments).
   * Returns array of attachment metadata: id, file_url, thumbnail_url, file_name, file_size, file_type.
   *
   * @param  array<int, \Illuminate\Http\UploadedFile>  $files
   * @param  string|null  $subFolder  Optional subfolder (e.g. "philtower") for local and S3 paths.
   * @return array<int, array{id: int, file_url: string|null, thumbnail_url: string|null, file_name: string, file_size: int, file_type: string}>
   */
  public function uploadFilesReturnAll(array $files, User $user, ?string $subFolder = null): array
  {
    $uploadedMedia = [];
    $files = is_array($files) ? $files : [$files];

    foreach ($files as $file) {
      try {
        if (!$file || !$file->isValid()) {
          continue;
        }
        $mime_type = explode("/", $file->getClientMimeType());
        $mediaType = $mime_type[0] ?? 'application';

        if ($this->useS3) {
          $data = $this->saveToS3($file, $user->id, $mediaType, $subFolder);
        } else {
          switch ($mediaType) {
            case 'image':
              $data = $this->saveImage($file, $user->id, $subFolder);
              break;
            case 'video':
              $data = $this->saveVideo($file, $user->id, $subFolder);
              break;
            default:
              $data = $this->saveFile($file, $user->id, $subFolder);
          }
        }
        $media = MediaLibrary::create($data);
        $uploadedMedia[] = [
          'id' => $media->id,
          'file_url' => $media->file_url,
          'thumbnail_url' => $media->thumbnail_url ?? $media->file_url,
          'file_name' => $media->file_name,
          'file_size' => (int) $media->file_size,
          'file_type' => $media->file_type ?? '',
        ];
      } catch (\Exception $e) {
        \Log::error('Media upload error: ' . $e->getMessage());
        continue;
      }
    }
    return $uploadedMedia;
  }

  /**
   * @param  string|null  $subFolder  Optional subfolder (e.g. "philtower") under year/month.
   */
  public function getStoragePath(?string $subFolder = null) 
	{
		$yr = date('Y');
		$mon = date('m');
		$relative = $yr.'/'.$mon;
		if ($subFolder) {
			$relative .= '/'.trim($subFolder, '/');
		}
		$path = Storage::disk('public')->path($relative);
		if (!Storage::disk('public')->exists($relative)) {
			Storage::disk('public')->makeDirectory($relative);
		}

		return [
			'storage_path' => $path,
			'public_path' => 'storage/'.$relative
		];
	}

	public function saveImage($file, $id, ?string $subFolder = null) 
	{
		$timestamp = time();
		$path = $this->getStoragePath($subFolder);

		$filename =  $file->getClientOriginalName();
		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		$image_size = getimagesize($file_path);

		$img = Image::make($file_path);
		$img->resize(150, 150, function ($constraint) {
				$constraint->aspectRatio();
		})->save($path['storage_path'].'/'.$timestamp.'-150x150-'.$filename);

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'width' => $image_size[0],
			'height' => $image_size[1],
			'file_dimensions' => $image_size[0].'x'.$image_size[1],
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$filename),
			'thumbnail_url' => asset($path['public_path'].'/'.$timestamp.'-150x150-'.$filename)
		];

		return $data;
	}

	public function saveVideo($file, $id, ?string $subFolder = null) 
	{
		$timestamp = time();
		$path = $this->getStoragePath($subFolder);

		$filename =  $file->getClientOriginalName();
		$video_filename = pathinfo($filename, PATHINFO_FILENAME);

		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$ffmpeg = FFMpeg::create([
			'ffmpeg.binaries' => config('app.ffmpeg'),
			'ffprobe.binaries' => config('app.ffprobe'),
		]);

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		// VIDEO CONVERSION PART
		$video = $ffmpeg->open($file_path);
		$dimensions = $video->getStreams()->videos()->first();
		$video->frame(TimeCode::fromSeconds(1))
					->addFilter(new CustomFrameFilter('scale=150x150'))
					->save($path['storage_path'].'/'.$timestamp.'-150x150-'.$video_filename.'.jpg');
		$video->save(new WebM(), $path['storage_path'].'/'.$timestamp.'-'.$video_filename.'.webm');

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'width' => $dimensions->get('width'),
			'height' => $dimensions->get('height'),
			'file_dimensions' => $dimensions->get('width').'x'.$dimensions->get('height'),
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$video_filename.'.webm'),
			'thumbnail_url' => asset($path['public_path'].'/'.$timestamp.'-150x150-'.$video_filename.'.jpg')
		];

		return $data;
	}

	public function saveFile($file, $id, ?string $subFolder = null) 
	{
		$timestamp = time();
		$path = $this->getStoragePath($subFolder);

		$filename =  $file->getClientOriginalName();
		$file_type = $file->getClientMimeType();
		$mime_type = explode("/", $file->getClientMimeType());
		$file_size = $file->getSize();

		$file_path = $file->move($path['storage_path'], $timestamp.'-'.$filename);
		$default_icon = '';
		switch($mime_type[0]) {
			case 'audio':
				$default_icon = '/assets/img/mp3-icon.png';
				break;
			case 'application' :
			case 'text':
				// Try SVG first, fallback to PNG if exists, otherwise use pdf-icon
				$file_icon_path = public_path('assets/img/file-icon.svg');
				if (file_exists($file_icon_path)) {
					$default_icon = '/assets/img/file-icon.svg';
				} elseif (file_exists(public_path('assets/img/file-icon.png'))) {
					$default_icon = '/assets/img/file-icon.png';
				} else {
					$default_icon = '/assets/img/pdf-icon.png'; // Fallback to existing icon
				}
				break;		
		}

		$data = [
			'user_id' => $id,
			'file_name' => $timestamp.'-'.$filename,
			'file_type' => $file_type,
			'file_size' => $file_size,
			'file_url' => asset($path['public_path'].'/'.$timestamp.'-'.$filename),
			'thumbnail_url' => asset($default_icon)
		];

		return $data;
	}

	protected function saveToS3($file, $userId, $type, ?string $subFolder = null)
  {
    $folder = config('app.name', 'BASE-CODE-PROJECT');
    if ($subFolder) {
      $folder = $folder . '/' . trim($subFolder, '/');
    }
    $filename = $file->getClientOriginalName();
    $file_type = $file->getClientMimeType();
    $file_size = $file->getSize();
    
    // Save file to a temp location first
    $tmpPath = $file->storeAs('tmp', uniqid() . '-' . $filename);
    $tmpFullPath = storage_path('app/' . $tmpPath);

    try {
      // Optimize image before upload if type is image
      if ($type === 'image') {
        if (class_exists('Image')) {
          $img = Image::make($tmpFullPath);
          $img->save($tmpFullPath, 85);
        }
        if (class_exists(OptimizerChainFactory::class)) {
          $optimizerChain = OptimizerChainFactory::create();
          $optimizerChain->optimize($tmpFullPath);
        }
      }

      $url = S3Helper::uploadFile($tmpFullPath, $filename, $folder);
      
      $data = [
        'user_id' => $userId,
        'file_name' => $filename,
        'file_type' => $file_type,
        'file_size' => $file_size,
        'file_url' => $url,
        'thumbnail_url' => $url, // S3: no thumb, use main url or generate thumb if needed
      ];
      
      if ($type === 'image') {
        $image = getimagesize($file->getPathname());
        if ($image) {
          $data['width'] = $image[0];
          $data['height'] = $image[1];
          $data['file_dimensions'] = $image[0] . 'x' . $image[1];
        }
      } elseif ($type === 'video') {
        $data['width'] = null;
        $data['height'] = null;
        $data['file_dimensions'] = null;
      }
      
      return $data;
    } finally {
      // Clean up temp file
      if (file_exists($tmpFullPath)) {
        @unlink($tmpFullPath);
      }
    }
  }

  /**
  * Storage folders.
  */
  public function folderList() 
  {
    if ($this->useS3) {
      return $this->getS3FolderList();
    } else {
      return $this->getLocalFolderList();
    }
  }

  /**
  * Get folder list from local storage.
  */
  protected function getLocalFolderList() 
  {
    $dates = [];
    try {
      $directories = Storage::disk('public')->directories('/');
      foreach($directories as $directory) {
        $sub_directories = Storage::disk('public')->directories('/'.$directory);
        foreach($sub_directories as $sub){
          $dates[] = [
            'value' => date_format(date_create(str_replace("/","-",$sub)."-01"), "d-m-Y"),
            'label' => date_format(date_create(str_replace("/","-",$sub)."-01"), "F Y")
          ];
        }
      }
    } catch (\Exception $e) {
      \Log::error('Local folder list error: ' . $e->getMessage());
    }
    return $dates;
  }

  /**
  * Get folder list from S3 storage.
  */
  protected function getS3FolderList() 
  {
    $dates = [];
    
    try {
      // Get unique year-month combinations from the database to extract dates
      $dateResults = MediaLibrary::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
        ->whereNotNull('file_url')
        ->where('file_url', 'like', '%s3%') // Only S3 files
        ->groupBy('year', 'month')
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();
      
      foreach ($dateResults as $result) {
        $year = $result->year;
        $month = str_pad($result->month, 2, '0', STR_PAD_LEFT);
        
        // Create a date object for the first day of the month
        $date = \Carbon\Carbon::createFromDate($year, $result->month, 1);
        
        $dates[] = [
          'value' => $date->format('d-m-Y'),
          'label' => $date->format('F Y')
        ];
      }

      // Sort dates in descending order (newest first)
      usort($dates, function($a, $b) {
        return strtotime($b['value']) - strtotime($a['value']);
      });

    } catch (\Exception $e) {
      // Log error and return empty array
      \Log::error('S3 folder list error: ' . $e->getMessage());
    }

    return $dates;
  }

  /**
   * Bulk delete media files and their physical files.
   */
  public function bulkDelete($ids)
  {
    // Ensure $ids is an array
    if (!is_array($ids)) {
      $ids = (array) $ids;
    }
    
    $mediaItems = MediaLibrary::whereIn('id', $ids)->get();
    
    foreach ($mediaItems as $media) {
      // Delete physical file if exists
      $this->deletePhysicalFile($media);
    }
    
    // Delete database records
    return MediaLibrary::whereIn('id', $ids)->delete();
  }

  /**
   * Delete physical file from storage.
   */
  protected function deletePhysicalFile(MediaLibrary $media)
  {
    try {
      if ($this->useS3) {
        // S3 deletion would be handled by S3Helper if needed
        // For now, we just delete the database record
        return;
      }
      
      // Extract file path from URL for local storage
      if ($media->file_url) {
        $urlPath = parse_url($media->file_url, PHP_URL_PATH);
        $filePath = public_path($urlPath);
        
        if (file_exists($filePath)) {
          @unlink($filePath);
        }
      }
      
      // Delete thumbnail if exists and different from main file
      if ($media->thumbnail_url && $media->thumbnail_url !== $media->file_url) {
        $thumbPath = parse_url($media->thumbnail_url, PHP_URL_PATH);
        $thumbFilePath = public_path($thumbPath);
        
        if (file_exists($thumbFilePath)) {
          @unlink($thumbFilePath);
        }
      }
    } catch (\Exception $e) {
      \Log::error('Error deleting physical file: ' . $e->getMessage());
    }
  }

  /**
   * Override destroy to also delete physical file.
   */
  public function destroy($id)
  {
    $media = MediaLibrary::findOrFail($id);
    $this->deletePhysicalFile($media);
    return parent::destroy($id);
  }
}