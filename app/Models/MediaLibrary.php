<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaLibrary extends Model
{
  use HasFactory;

  /**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'file_name',
		'file_type',
		'file_size',
		'width',
		'height',
		'file_dimensions',
		'file_url',
		'thumbnail_url',
		'caption',
		'short_descriptions',
	];

    /**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'file_size' => 'integer',
		'width' => 'integer',
		'height' => 'integer',
	];

  /**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'media_libraries';

	/**
	 * Get the user that uploaded the media.
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	/**
	 * Scope a query to filter by file type.
	 */
	public function scopeOfType($query, string $type)
	{
		return $query->where('file_type', 'LIKE', "%{$type}%");
	}

	/**
	 * Scope a query to filter by date (year-month).
	 */
	public function scopeByDate($query, string $date)
	{
		$timestamp = strtotime($date);
		$year = date('Y', $timestamp);
		$month = date('m', $timestamp);
		
		return $query->whereYear('created_at', $year)
					->whereMonth('created_at', $month);
	}

	/**
	 * Scope a query to search media.
	 */
	public function scopeSearch($query, string $search)
	{
		return $query->where(function ($q) use ($search) {
			$q->where('file_name', 'LIKE', "%{$search}%")
			  ->orWhere('file_type', 'LIKE', "%{$search}%")
			  ->orWhere('file_dimensions', 'LIKE', "%{$search}%")
			  ->orWhere('caption', 'LIKE', "%{$search}%")
			  ->orWhere('short_descriptions', 'LIKE', "%{$search}%");
		});
	}

	/**
	 * Get formatted file size.
	 */
	public function getFormattedFileSizeAttribute(): string
	{
		$bytes = (int) $this->file_size;
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		
		for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
			$bytes /= 1024;
		}
		
		return round($bytes, 2) . ' ' . $units[$i];
	}

	/**
	 * Check if media is an image.
	 */
	public function isImage(): bool
	{
		return str_starts_with($this->file_type, 'image/');
	}

	/**
	 * Check if media is a video.
	 */
	public function isVideo(): bool
	{
		return str_starts_with($this->file_type, 'video/');
	}

	/**
	 * Check if media is an audio file.
	 */
	public function isAudio(): bool
	{
		return str_starts_with($this->file_type, 'audio/');
	}

	/**
	 * Get the file extension.
	 */
	public function getExtensionAttribute(): string
	{
		return pathinfo($this->file_name, PATHINFO_EXTENSION);
	}
}
