<?php

namespace App\Http\Controllers\Api\Support;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreTicketRequestRequest;
use App\Http\Requests\UpdateTicketRequestRequest;
use App\Services\MediaService;
use App\Services\Support\TicketRequestService;
use App\Services\MessageService;

/**
 * All Tickets: ticket_requests CRUD. Soft deletes, bulk actions.
 * Attachments are uploaded via MediaService and stored as attachment_metadata (file_url, thumbnail_url, etc.).
 */
class TicketRequestController extends BaseController
{
    protected MediaService $mediaService;

    public function __construct(
        TicketRequestService $ticketRequestService,
        MessageService $messageService,
        MediaService $mediaService
    ) {
        parent::__construct($ticketRequestService, $messageService);
        $this->mediaService = $mediaService;
    }

    /**
     * Upload attachment files to philtower folder (local or S3). Returns attachment_metadata for saving on ticket.
     * Frontend calls this first with FormData (files only), then includes returned metadata in ticket store/update.
     */
    public function uploadAttachments(Request $request)
    {
        try {
            $files = $this->collectUploadedFiles($request);
            if (empty($files)) {
                return response()->json(['message' => 'No valid files uploaded.', 'attachment_metadata' => []], 422);
            }
            $uploaded = $this->mediaService->uploadFilesReturnAll($files, $request->user(), 'philtower');
            $attachment_metadata = array_map(function ($m) {
                return [
                    'name' => $m['file_name'] ?? 'file',
                    'size' => (int) ($m['file_size'] ?? 0),
                    'type' => $m['file_type'] ?? '',
                    'file_url' => $m['file_url'] ?? null,
                    'thumbnail_url' => $m['thumbnail_url'] ?? $m['file_url'] ?? null,
                    'media_id' => $m['id'] ?? null,
                ];
            }, $uploaded);
            return response()->json(['attachment_metadata' => $attachment_metadata], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function index()
    {
        try {
            $perPage = (int) request('per_page', 10);
            $trash = (bool) request('trash', false);
            return $this->service->list($perPage, $trash);
        } catch (\Throwable $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function store(StoreTicketRequestRequest $request)
    {
        try {
            $data = $this->normalizeTicketRequestData($request);
            $resource = $this->service->store($data);
            return response($resource, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function show($id)
    {
        try {
            $item = $this->service->show((int) $id);
            return response()->json(['data' => $item]);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function update(UpdateTicketRequestRequest $request, $id)
    {
        try {
            $data = $this->normalizeTicketRequestData($request);
            $resource = $this->service->update($data, (int) $id);
            return response($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    /**
     * Normalize request data for store/update. attachment_metadata is sent as JSON from frontend
     * (after uploading files via POST upload-attachments).
     */
    protected function normalizeTicketRequestData(StoreTicketRequestRequest|UpdateTicketRequestRequest $request): array
    {
        $data = $request->validated();

        $meta = $data['attachment_metadata'] ?? $request->input('attachment_metadata');
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $data['attachment_metadata'] = is_array($decoded) ? $decoded : null;
        }
        if (isset($data['attachment_metadata']) && is_array($data['attachment_metadata']) && empty($data['attachment_metadata'])) {
            $data['attachment_metadata'] = null;
        }
        unset($data['attachments']);

        return $data;
    }

    /**
     * Collect every uploaded file from the request regardless of form field name.
     * Handles attachments, attachments[], or any other key that contains UploadedFile(s).
     *
     * @param  Request|StoreTicketRequestRequest|UpdateTicketRequestRequest  $request
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    protected function collectUploadedFiles($request): array
    {
        $collected = [];
        foreach ($request->allFiles() as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                if ($value->isValid()) {
                    $collected[] = $value;
                }
            } elseif (is_array($value)) {
                foreach ($value as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                        $collected[] = $file;
                    }
                }
            }
        }
        return $collected;
    }

    public function destroy($id)
    {
        return parent::destroy($id);
    }

    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
    }

    public function getTrashed()
    {
        return parent::getTrashed();
    }

    public function restore($id)
    {
        return parent::restore($id);
    }

    public function forceDelete($id)
    {
        return parent::forceDelete($id);
    }

    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }
}
