<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Http\Requests\BulkDeleteMediaRequest;
use App\Services\MediaService;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends BaseController
{
	public function __construct(MediaService $mediaService, MessageService $messageService)
  {
    parent::__construct($mediaService, $messageService);
  }

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreMediaRequest $request)
	{
		try {
			$user = $request->user();
			$files = $request->file('files');

			$media = $this->service->uploadFiles($files, $user);
			return response($media, 201);

		} catch (\Exception $e) {
      return $this->messageService->responseError($e->getMessage());
    }
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update($id, UpdateMediaRequest $request)
	{
		try {
			$mediaLibrary = $this->service->update($request->validated(), (int) $id);
			return response($mediaLibrary, 200);
		} catch (\Exception $e) {
      return $this->messageService->responseError($e->getMessage());
    }
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy($id)
	{
		try {
			$this->service->destroy((int) $id);
			$message = 'Media has been deleted permanently.';
      return response(compact('message'), 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError($e->getMessage());
    }
	}

	public function bulkDelete(Request $request) 
	{
		try {
			// Validate the request using the Form Request rules
			$validator = Validator::make($request->all(), [
				'ids' => 'required|array',
				'ids.*' => 'required|integer|exists:media_libraries,id',
			], [
				'ids.required' => 'Please select at least one media item to delete.',
				'ids.array' => 'IDs must be an array.',
				'ids.*.required' => 'Each ID is required.',
				'ids.*.integer' => 'Each ID must be an integer.',
				'ids.*.exists' => 'One or more selected media items do not exist.',
			]);

			if ($validator->fails()) {
				return response(['errors' => $validator->errors()], 422);
			}
			
			$this->service->bulkDelete($request->ids);
			$message = 'Media/s has been deleted permanently.';
      return response(compact('message'), 200);
		} catch (\Exception $e) {
      return $this->messageService->responseError($e->getMessage());
    }
	}

	public function dateFolder() 
	{
		try {
			return response($this->service->folderList(), 200);
		} catch (\Exception $e) {
      return $this->messageService->responseError($e->getMessage());
    }
	}
}
