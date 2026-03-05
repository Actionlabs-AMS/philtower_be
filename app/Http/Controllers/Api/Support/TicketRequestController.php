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
     * Merge validated data with uploaded attachments (MediaService).
     * Supports multipart: attachment_metadata may be JSON string; new files in attachments[].
     */
    protected function normalizeTicketRequestData(StoreTicketRequestRequest|UpdateTicketRequestRequest $request): array
    {
        $data = $request->validated();

        // Get attachment_metadata from validated() or raw input (multipart can omit from validated in some setups)
        $meta = $data['attachment_metadata'] ?? $request->input('attachment_metadata');
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $data['attachment_metadata'] = is_array($decoded) ? $decoded : [];
        }
        $existingMeta = is_array($data['attachment_metadata'] ?? null) ? $data['attachment_metadata'] : [];

        // Get uploaded files: PHP parses "attachments[]" as key "attachments" with array value
        $files = $request->file('attachments');
        if (empty($files) && $request->hasFile('attachments[]')) {
            $files = $request->file('attachments[]');
        }
        if (empty($files)) {
            $allFiles = $request->allFiles();
            foreach (['attachments', 'attachments[]'] as $key) {
                if (!empty($allFiles[$key])) {
                    $files = is_array($allFiles[$key]) ? $allFiles[$key] : [$allFiles[$key]];
                    break;
                }
            }
        }
        if (!empty($files) && !is_array($files)) {
            $files = [$files];
        }
        if (!empty($files)) {
            $files = is_array($files) ? array_values($files) : [$files];
            $files = array_filter($files, fn ($f) => $f && (is_object($f) && method_exists($f, 'isValid') ? $f->isValid() : true));
            if (!empty($files)) {
                $uploaded = $this->mediaService->uploadFilesReturnAll($files, $request->user(), 'philtower');
                $newMeta = array_map(function ($m) {
                    return [
                        'name' => $m['file_name'] ?? 'file',
                        'size' => (int) ($m['file_size'] ?? 0),
                        'type' => $m['file_type'] ?? '',
                        'file_url' => $m['file_url'] ?? null,
                        'thumbnail_url' => $m['thumbnail_url'] ?? $m['file_url'] ?? null,
                        'media_id' => $m['id'] ?? null,
                    ];
                }, $uploaded);
                $data['attachment_metadata'] = array_merge($existingMeta, $newMeta);
            } else {
                $data['attachment_metadata'] = !empty($existingMeta) ? $existingMeta : null;
            }
        } else {
            $data['attachment_metadata'] = !empty($existingMeta) ? $existingMeta : null;
        }

        unset($data['attachments']);

        return $data;
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
