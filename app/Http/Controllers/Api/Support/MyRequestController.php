<?php

namespace App\Http\Controllers\Api\Support;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreTicketRequestRequest;
use App\Http\Requests\UpdateTicketRequestRequest;
use App\Models\Support\TicketRequest;
use App\Services\MediaService;
use App\Services\MessageService;
use App\Services\Support\TicketRequestService;

/**
 * My Request: ticket_requests scoped to the authenticated user (requestor).
 * All actions filter or authorize by user_id = auth()->id().
 */
class MyRequestController extends BaseController
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
     * Upload attachment files for a ticket request (requestor).
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

    public function index()
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $perPage = (int) request('per_page', 10);
            $trash = (bool) request('trash', false);
            return $this->service->listForUser($userId, $perPage, $trash);
        } catch (\Throwable $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function getTrashed()
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $perPage = (int) request('per_page', 10);
            return $this->service->listForUser($userId, $perPage, true);
        } catch (\Throwable $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function show($id)
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $item = $this->service->showForUser((int) $id, $userId);
            return response()->json(['data' => $item]);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function store(StoreTicketRequestRequest $request)
    {
        try {
            $data = $this->normalizeTicketRequestData($request);
            $data['user_id'] = $request->user()?->id;
            // Allow draft: only set submitted_at when explicitly submitting (non-null)
            if (! array_key_exists('submitted_at', $data) || $data['submitted_at'] === null || $data['submitted_at'] === '') {
                $data['submitted_at'] = null;
            } else {
                $data['submitted_at'] = $data['submitted_at'] ?: now();
            }
            $resource = $this->service->store($data);
            return response($resource, 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function update(UpdateTicketRequestRequest $request, $id)
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ticket = TicketRequest::find($id);
            if (!$ticket || (int) $ticket->user_id !== (int) $userId) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $data = $this->normalizeTicketRequestData($request);
            $resource = $this->service->update($data, (int) $id);
            return response($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ticket = TicketRequest::find($id);
            if (!$ticket || (int) $ticket->user_id !== (int) $userId) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $this->service->destroy((int) $id);
            return response(['message' => 'Resource has been moved to trash.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function restore($id)
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ticket = TicketRequest::withTrashed()->find($id);
            if (!$ticket || (int) $ticket->user_id !== (int) $userId) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $item = $this->service->restore((int) $id);
            return response([
                'message' => 'Resource has been restored.',
                'resource' => $item,
            ], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function forceDelete($id)
    {
        try {
            $userId = request()->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ticket = TicketRequest::withTrashed()->find($id);
            if (!$ticket || (int) $ticket->user_id !== (int) $userId) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            $this->service->forceDelete((int) $id);
            return response(['message' => 'Resource has been permanently deleted.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ids = array_filter(array_map('intval', (array) ($request->ids ?? [])));
            $allowedIds = TicketRequest::whereIn('id', $ids)->where('user_id', $userId)->pluck('id')->toArray();
            if (empty($allowedIds)) {
                return response(['message' => 'No resources to delete.'], 200);
            }
            $this->service->bulkDelete($allowedIds);
            return response(['message' => 'Resources have been deleted.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ids = array_filter(array_map('intval', (array) ($request->ids ?? [])));
            $allowedIds = TicketRequest::withTrashed()->whereIn('id', $ids)->where('user_id', $userId)->pluck('id')->toArray();
            if (empty($allowedIds)) {
                return response(['message' => 'No resources to restore.'], 200);
            }
            $this->service->bulkRestore($allowedIds);
            return response(['message' => 'Resources have been restored.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function bulkForceDelete(Request $request)
    {
        try {
            $userId = $request->user()?->id;
            if (!$userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $ids = array_filter(array_map('intval', (array) ($request->ids ?? [])));
            $allowedIds = TicketRequest::withTrashed()->whereIn('id', $ids)->where('user_id', $userId)->pluck('id')->toArray();
            if (empty($allowedIds)) {
                return response(['message' => 'No resources to delete.'], 200);
            }
            $this->service->bulkForceDelete($allowedIds);
            return response(['message' => 'Resources have been permanently deleted.'], 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

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
}
