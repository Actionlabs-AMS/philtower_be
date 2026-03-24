<?php

namespace App\Http\Controllers\Api\Support;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreTicketRequestRequest;
use App\Http\Requests\UpdateTicketRequestRequest;
use App\Services\MediaService;
use App\Services\Support\TicketRequestService;
use App\Services\MessageService;
use App\Models\User;
use App\Models\Support\TicketRelationship;

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
            return $this->service->list($perPage, $trash, request()->user());
        } catch (\Throwable $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function store(StoreTicketRequestRequest $request)
    {
        try {
            $data = $this->normalizeTicketRequestData($request);
            if (! array_key_exists('user_id', $data) || $data['user_id'] === null) {
                $data['user_id'] = $request->user()?->id;
            }
            if (empty($data['submitted_at'])) {
                $data['submitted_at'] = now();
            }
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

    /**
     * Approve a ticket request (set status to approved). For tickets with for_approval=1.
     */
    public function approve($id)
    {
        try {
            $resource = $this->service->approve((int) $id);
            return response()->json($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    /**
     * Reject/cancel a ticket request (set status to rejected).
     */
    public function reject($id)
    {
        try {
            $resource = $this->service->reject((int) $id);
            return response()->json($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    /**
     * Reassign a ticket to another user. Logs who reassigned, previous and new assignee, and timestamp.
     * Expects JSON body: { "new_assignee_id": <user_id> } (or "newAssigneeId").
     */
    public function reassign(Request $request, $id)
    {
        try {
            $newAssigneeId = (int) ($request->input('new_assignee_id') ?? $request->input('newAssigneeId'));
            if ($newAssigneeId < 1) {
                return response()->json([
                    'message' => 'new_assignee_id is required and must be a valid user id.',
                    'status' => false,
                    'status_code' => 422,
                ], 422);
            }
            $resource = $this->service->reassign((int) $id, $newAssigneeId);
            return response($resource, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Ticket not found.',
                'status' => false,
                'status_code' => 404,
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false,
                'status_code' => 422,
            ], 422);
        } catch (QueryException $e) {
            report($e);
            return response()->json([
                'message' => 'Database error while saving. If you recently added new fields, run: php artisan migrate',
                'status' => false,
                'status_code' => 422,
            ], 422);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'status' => false,
                    'status_code' => 422,
                ], 422);
            }
            return $this->messageService->responseError($e);
        }
    }

    public function requestApproval(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'approver_ids' => ['required', 'array', 'min:1'],
                'approver_ids.*' => ['integer', 'exists:users,id'],
                'reason' => ['required', 'string', 'min:5'],
                'department' => ['nullable', 'string', 'max:100'],
            ]);

            $approverCount = User::query()
                ->whereIn('id', $validated['approver_ids'])
                ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['approver']))
                ->count();
            if ($approverCount !== count($validated['approver_ids'])) {
                return response()->json([
                    'message' => 'All selected users must have the Approver role.',
                    'status' => false,
                    'status_code' => 422,
                ], 422);
            }

            $resource = $this->service->requestManualApproval(
                (int) $id,
                $validated['approver_ids'],
                $validated['reason'],
                $validated['department'] ?? null
            );
            return response()->json($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function relationships($id)
    {
        $items = TicketRelationship::query()
            ->where('source_ticket_id', (int) $id)
            ->with('targetTicket:id,request_number')
            ->get();
        return response()->json(['data' => $items]);
    }

    public function createRelationship(Request $request, $id)
    {
        $validated = $request->validate([
            'target_ticket_id' => ['required', 'integer', 'exists:ticket_requests,id'],
            'relationship_type' => ['required', 'string', 'in:duplicate_of,parent_of,child_of,relates_to'],
        ]);
        if ((int) $id === (int) $validated['target_ticket_id']) {
            return response()->json(['message' => 'Cannot relate a ticket to itself.'], 422);
        }
        $item = TicketRelationship::create([
            'source_ticket_id' => (int) $id,
            'target_ticket_id' => (int) $validated['target_ticket_id'],
            'relationship_type' => $validated['relationship_type'],
            'created_by' => auth()->id(),
        ]);
        return response()->json(['data' => $item], 201);
    }

    public function deleteRelationship($id, $relationshipId)
    {
        $item = TicketRelationship::query()
            ->where('source_ticket_id', (int) $id)
            ->where('id', (int) $relationshipId)
            ->firstOrFail();
        $item->delete();
        return response()->json(['message' => 'Relationship removed.']);
    }
}
