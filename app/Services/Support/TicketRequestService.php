<?php

namespace App\Services\Support;

use App\Helpers\SlaHelper;
use App\Http\Resources\TicketRequestResource;
use App\Models\Support\ServiceType;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use App\Models\Support\TicketUpdate;
use App\Models\Support\SlaClock;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketRequestService extends BaseService
{
    public const FOR_APPROVAL_YES = 1;
    public const FOR_APPROVAL_NO = 2;
    public const FOR_APPROVAL_AUTO = 3;

    public function __construct()
    {
        parent::__construct(new TicketRequestResource(new TicketRequest()), new TicketRequest());
    }

    /**
     * Set for_approval from selected service type: 1=yes, 2=no, 3=auto.
     * If for_approval is YES, set ticket_status_id to the "for_approval" status.
     */
    protected function applyForApprovalFromServiceType(array $data): array
    {
        $serviceTypeId = $data['service_type_id'] ?? null;
        if ($serviceTypeId) {
            $serviceType = ServiceType::find($serviceTypeId);
            if ($serviceType) {
                $approval = $serviceType->approval;
                $data['for_approval'] = ($approval === true || $approval === 1) ? self::FOR_APPROVAL_YES : self::FOR_APPROVAL_NO;
                if ($data['for_approval'] === self::FOR_APPROVAL_YES) {
                    $forApprovalStatus = TicketStatus::where('code', 'for_approval')->first();
                    if ($forApprovalStatus) {
                        $data['ticket_status_id'] = $forApprovalStatus->id;
                    }
                }
            }
        } else {
            $data['for_approval'] = $data['for_approval'] ?? self::FOR_APPROVAL_AUTO;
        }
        return $data;
    }

    /**
     * Normalize attachment_metadata for persistence.
     * Preserves: name, size, type, file_url, thumbnail_url, media_id (from MediaService uploads).
     */
    protected function normalizeAttachmentMetadata(array $data): array
    {
        if (!array_key_exists('attachment_metadata', $data)) {
            return $data;
        }
        $meta = $data['attachment_metadata'];
        if ($meta === null || $meta === '') {
            $data['attachment_metadata'] = null;
            return $data;
        }
        if (!is_array($meta)) {
            $data['attachment_metadata'] = null;
            return $data;
        }
        $data['attachment_metadata'] = array_values(array_map(function ($item) {
            if (!is_array($item)) {
                return ['name' => 'file', 'size' => 0, 'type' => '', 'file_url' => null, 'thumbnail_url' => null, 'media_id' => null];
            }
            $normalized = [
                'name' => $item['name'] ?? $item['file_name'] ?? 'file',
                'size' => isset($item['size']) ? (int) $item['size'] : 0,
                'type' => $item['type'] ?? '',
            ];
            if (isset($item['file_url'])) {
                $normalized['file_url'] = $item['file_url'];
            }
            if (isset($item['thumbnail_url'])) {
                $normalized['thumbnail_url'] = $item['thumbnail_url'];
            }
            if (array_key_exists('media_id', $item) && $item['media_id'] !== null) {
                $normalized['media_id'] = (int) $item['media_id'];
            }
            return $normalized;
        }, $meta));
        return $data;
    }

    /**
     * Strip nulls for columns that have non-null DB defaults so the database can apply defaults.
     */
    protected function prepareDataForPersistence(array $data): array
    {
        $nullableDefaults = ['slas_id' => 3, 'ticket_status_id' => 1, 'for_approval' => self::FOR_APPROVAL_AUTO];
        foreach ($nullableDefaults as $key => $default) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public function store(array $data)
    {
        if (!array_key_exists('created_by', $data)) {
            $data['created_by'] = auth()->id();
        }
        // Allow technicians to create tickets on behalf of a requester user
        if (isset($data['requester_user_id']) && $data['requester_user_id']) {
            $data['user_id'] = (int) $data['requester_user_id'];
        }
        unset($data['requester_user_id']);

        $data = $this->applyForApprovalFromServiceType($data);
        $data = $this->normalizeAttachmentMetadata($data);
        $data = $this->prepareDataForPersistence($data);
        $resource = parent::store($data);
        $model = $resource->resource;
        if ($model instanceof TicketRequest) {
            $model->loadMissing(['ticketStatus']);
            event(new TicketCreated($model));

            // If the ticket starts in a status that triggers notifications (e.g. for_approval),
            // fire the status-changed event so status-based listeners run on creation too.
            if ($model->ticketStatus?->code === 'for_approval') {
                event(new TicketStatusChanged(
                    $model,
                    null,
                    $model->ticketStatus?->label,
                    null,
                    (int) $model->ticket_status_id
                ));
            }

            // Fire TicketAssigned if assigned at creation time
            if ($model->assigned_to) {
                event(new TicketAssigned($model));
            }
        }
        return $resource;
    }

    public function update(array $data, int $id)
    {
        $data = $this->applyForApprovalFromServiceType($data);
        $data = $this->normalizeAttachmentMetadata($data);
        $data = $this->prepareDataForPersistence($data);

        $model = TicketRequest::with('ticketStatus')->findOrFail($id);
        $oldStatusId = $model->ticket_status_id;
        $oldLabel = $model->ticketStatus?->label ?? 'Unknown';
        $oldAssignedTo = $model->assigned_to;

        $out = parent::update($data, $id);
        $model->refresh()->load(['ticketStatus']);
        $newStatusId = $model->ticket_status_id;

        if (array_key_exists('ticket_status_id', $data) && (int) $oldStatusId !== (int) $newStatusId) {
            $newLabel = $model->ticketStatus?->label ?? 'Unknown';
            TicketUpdate::create([
                'ticket_request_id' => $id,
                'type' => TicketUpdate::TYPE_STATUS_CHANGE,
                'content' => sprintf('Status changed from %s to %s', $oldLabel, $newLabel),
                'is_internal' => false,
            ]);
            SlaHelper::manageTicketRequestSla($model, false);
            event(new TicketStatusChanged($model, $oldLabel, $newLabel, (int) $oldStatusId, (int) $newStatusId));

            // Fire TicketClosed when status transitions to 'closed'
            if ($model->ticketStatus?->code === 'closed') {
                event(new TicketClosed($model));
            }
        }

        // Fire TicketAssigned when assigned_to changes to a new user
        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] && (int) $data['assigned_to'] !== (int) $oldAssignedTo) {
            event(new TicketAssigned($model));
        }

        return $out;
    }

    /**
     * List ticket requests (paginated). Optional trash view.
     * If user is given and cannot view all tickets, only tickets assigned to that user are returned.
     *
     * @param  int  $perPage
     * @param  bool  $trash
     * @param  User|null  $user  Current user; when null, uses auth()->user()
     */
    public function list($perPage = 10, $trash = false, ?User $user = null): AnonymousResourceCollection
    {
        $user = $user ?? auth()->user();
        $baseQuery = TicketRequest::query();
        if ($user && ! $user->canViewAllTickets()) {
            $baseQuery->where('assigned_to', $user->id);
        }

        $all = (clone $baseQuery)->count();
        $trashed = (clone $baseQuery)->onlyTrashed()->count();
        $my = $user ? (clone $baseQuery)->where('assigned_to', $user->id)->count() : 0;
        $unassigned = (clone $baseQuery)->whereNull('assigned_to')->count();

        $query = (clone $baseQuery)->with(['ticketStatus', 'serviceType', 'ticketPriority', 'assignedTo', 'createdBy']);
        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_email', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('ticket_status_id')) {
            $query->where('ticket_status_id', request('ticket_status_id'));
        }

        $statusCode = request('status');
        if (is_string($statusCode) && $statusCode !== '') {
            $codeToIds = [
                'open' => ['new', 'assigned'],
                'closed' => ['closed', 'cancelled'],
                'in_progress' => ['in_progress'],
                'pending' => ['pending'],
                'resolved' => ['resolved'],
            ];
            $codes = $codeToIds[$statusCode] ?? [$statusCode];
            $statusIds = TicketStatus::whereIn('code', $codes)->pluck('id')->toArray();
            if (! empty($statusIds)) {
                $query->whereIn('ticket_status_id', $statusIds);
            }
        }

        $slaStatus = request('sla_status');
        if (is_string($slaStatus) && $slaStatus === 'breached') {
            $breachedIds = \App\Models\Support\SlaClock::query()
                ->where('entity_type', 'ticket_request')
                ->where('status', 'breached')
                ->pluck('entity_id')
                ->unique()
                ->values()
                ->all();
            if (! empty($breachedIds)) {
                $query->whereIn('ticket_requests.id', $breachedIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Tab filters: assigned_to (user id), unassigned (1), or legacy assigned=me|unassigned
        if (request()->filled('assigned_to')) {
            $query->where('assigned_to', (int) request('assigned_to'));
        } elseif (request('unassigned') === 1 || request('unassigned') === '1') {
            $query->whereNull('assigned_to');
        } else {
            $assignedFilter = request('assigned');
            if (is_string($assignedFilter) && $assignedFilter !== '') {
                if ($assignedFilter === 'me' && $user) {
                    $query->where('assigned_to', $user->id);
                } elseif ($assignedFilter === 'unassigned') {
                    $query->whereNull('assigned_to');
                }
            }
        }

        if (request()->filled('for_approval')) {
            $query->where('for_approval', request('for_approval'));
        }

        // Sorting: whitelist + map fields (frontend sends table field keys like `service_type_name`)
        $requestedOrder = (string) request('order', 'created_at');
        $requestedSort = strtolower((string) request('sort', 'desc'));
        $sortDir = in_array($requestedSort, ['asc', 'desc'], true) ? $requestedSort : 'desc';

        $orderMap = [
            // Direct columns
            'id' => 'ticket_requests.id',
            'request_number' => 'ticket_requests.request_number',
            'contact_name' => 'ticket_requests.contact_name',
            'contact_email' => 'ticket_requests.contact_email',
            'for_approval' => 'ticket_requests.for_approval',
            'created_at' => 'ticket_requests.created_at',
            'created_at_human' => 'ticket_requests.created_at',
            'updated_at' => 'ticket_requests.updated_at',
            // Relation display fields (require join)
            'service_type_name' => 'service_types.name',
            'ticket_status_label' => 'ticket_statuses.label',
            // ticket_priorities.level via ticket_priority_id (join in branch below)
            'ticket_priority_level' => 'ticket_priorities.level',
        ];

        $orderBy = $orderMap[$requestedOrder] ?? $orderMap['created_at'];

        // Join related tables only when sorting by their display fields
        if ($requestedOrder === 'service_type_name') {
            $query->leftJoin('service_types', 'ticket_requests.service_type_id', '=', 'service_types.id')
                ->select('ticket_requests.*');
        } elseif ($requestedOrder === 'ticket_status_label') {
            $query->leftJoin('ticket_statuses', 'ticket_requests.ticket_status_id', '=', 'ticket_statuses.id')
                ->select('ticket_requests.*');
        } elseif ($requestedOrder === 'ticket_priority_level') {
            $query->leftJoin('ticket_priorities', 'ticket_requests.ticket_priority_id', '=', 'ticket_priorities.id')
                ->select('ticket_requests.*');
        }

        if ($requestedOrder === 'ticket_priority_level') {
            // Null priority last: asc = lowest level first; desc = highest level first
            if ($sortDir === 'asc') {
                $query->orderByRaw('COALESCE(ticket_priorities.level, 999999) ASC');
            } else {
                $query->orderByRaw('COALESCE(ticket_priorities.level, 0) DESC');
            }
        } else {
            $query->orderBy($orderBy, $sortDir);
        }

        return TicketRequestResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'meta' => [
                'all' => $all,
                'trashed' => $trashed,
                'my' => $my,
                'unassigned' => $unassigned,
            ],
        ]);
    }

    public function show(int $id)
    {
        $model = TicketRequest::withTrashed()->with(['ticketStatus', 'serviceType', 'sla', 'ticketPriority', 'user', 'assignedTo', 'createdBy'])->findOrFail($id);
        return TicketRequestResource::make($model);
    }

    /**
     * Set ticket status to approved (for_approval=1 tickets).
     */
    public function approve(int $id)
    {
        $status = TicketStatus::where('code', 'approved')->firstOrFail();
        $model = TicketRequest::with('ticketStatus')->findOrFail($id);
        $oldLabel = $model->ticketStatus?->label ?? 'Unknown';
        $oldStatusId = $model->ticket_status_id;
        $model->ticket_status_id = $status->id;
        $model->save();
        $model->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo']);
        event(new TicketStatusChanged($model, $oldLabel, $status->label, (int) $oldStatusId, $status->id));
        return TicketRequestResource::make($model);
    }

    /**
     * Set ticket status to rejected (cancel approval).
     */
    public function reject(int $id)
    {
        $status = TicketStatus::where('code', 'rejected')->firstOrFail();
        $model = TicketRequest::with('ticketStatus')->findOrFail($id);
        $oldLabel = $model->ticketStatus?->label ?? 'Unknown';
        $oldStatusId = $model->ticket_status_id;
        $model->ticket_status_id = $status->id;
        $model->save();
        $model->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo']);
        event(new TicketStatusChanged($model, $oldLabel, $status->label, (int) $oldStatusId, $status->id));
        return TicketRequestResource::make($model);
    }

    public function requestManualApproval(int $id, array $approverIds, string $reason, ?string $department = null)
    {
        $model = TicketRequest::with('ticketStatus')->findOrFail($id);
        $oldLabel = $model->ticketStatus?->label ?? 'Unknown';
        $oldStatusId = $model->ticket_status_id;

        $pendingManualStatus = TicketStatus::where('code', 'pending_manual_approval')->first()
            ?? TicketStatus::where('code', 'for_approval')->first();

        $payload = [
            'approver_ids' => array_values(array_unique(array_map('intval', $approverIds))),
            'reason' => $reason,
            'department' => $department,
            'requested_by' => auth()->id(),
            'requested_at' => now()->toIso8601String(),
        ];

        $model->for_approval = self::FOR_APPROVAL_YES;
        $model->manual_approval_data = $payload;
        if ($pendingManualStatus) {
            $model->ticket_status_id = $pendingManualStatus->id;
        }
        $model->save();

        // Pause active SLA clock while manual approval is pending.
        $clock = SlaClock::query()
            ->where('entity_type', 'ticket_request')
            ->where('entity_id', $model->id)
            ->whereNull('completed_at')
            ->latest('id')
            ->first();
        if ($clock && $clock->paused_at === null) {
            $clock->paused_at = now();
            $clock->status = 'paused';
            $clock->save();
        }

        TicketUpdate::create([
            'ticket_request_id' => $model->id,
            'user_id' => auth()->id() ?? $model->user_id,
            'type' => TicketUpdate::TYPE_STATUS_CHANGE,
            'content' => 'Manual approval requested: ' . $reason,
            'metadata' => $payload,
            'is_internal' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $model->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo', 'createdBy']);
        event(new TicketStatusChanged(
            $model,
            $oldLabel,
            $model->ticketStatus?->label ?? 'Pending Manual Approval',
            (int) $oldStatusId,
            (int) $model->ticket_status_id
        ));
        return TicketRequestResource::make($model);
    }

    /**
     * Reassign a ticket to a new user. Logs the action as a system comment in ticket_updates.
     *
     * @param  int  $ticketId
     * @param  int  $newAssigneeId
     * @return \App\Http\Resources\TicketRequestResource
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException  When new assignee is invalid
     */
    public function reassign(int $ticketId, int $newAssigneeId)
    {
        $ticket = TicketRequest::findOrFail($ticketId);
        $newAssignee = User::find($newAssigneeId);
        if (! $newAssignee) {
            throw new \InvalidArgumentException('Invalid assignee.');
        }

        $oldAssigneeId = $ticket->assigned_to;
        $oldAssignee = $oldAssigneeId ? User::find($oldAssigneeId) : null;
        $oldName = $oldAssignee ? ($oldAssignee->user_login ?? 'User #' . $oldAssigneeId) : 'Unassigned';
        $newName = $newAssignee->user_login ?? 'User #' . $newAssigneeId;

        $ticket->assigned_to = $newAssigneeId;
        $ticket->save();

        TicketUpdate::create([
            'ticket_request_id' => $ticketId,
            'type' => TicketUpdate::TYPE_REASSIGNMENT,
            'content' => sprintf('Ticket reassigned from %s to %s', $oldName, $newName),
            'metadata' => [
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $newAssigneeId,
                'performed_by' => auth()->id(),
            ],
            'is_internal' => false,
        ]);

        $ticket->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo']);
        event(new TicketAssigned($ticket));

        return TicketRequestResource::make($ticket);
    }

    /**
     * Dashboard stats for requestor (current user's ticket_requests only).
     * Returns total_requests, in_progress (in_progress+assigned+new+pending+for_approval+approved), open_requests (same as in_progress for client view), resolved_requests (resolved+closed).
     */
    public function getRequestorDashboardStats(int $userId): array
    {
        $base = TicketRequest::query()->where('user_id', $userId);
        $total = (clone $base)->count();

        $statusCodes = \App\Models\Support\TicketStatus::all()->keyBy('id');
        $openCodes = ['new', 'assigned', 'pending', 'for_approval', 'approved'];
        $inProgressCodes = ['in_progress'];
        $resolvedCodes = ['resolved', 'closed'];

        $openStatusIds = $statusCodes->filter(fn ($s) => in_array($s->code, $openCodes, true))->keys()->toArray();
        $inProgressStatusIds = $statusCodes->filter(fn ($s) => in_array($s->code, $inProgressCodes, true))->keys()->toArray();
        $resolvedStatusIds = $statusCodes->filter(fn ($s) => in_array($s->code, $resolvedCodes, true))->keys()->toArray();

        $in_progress = empty($inProgressStatusIds) ? 0 : (clone $base)->whereIn('ticket_status_id', $inProgressStatusIds)->count();
        $open_requests = empty($openStatusIds) ? 0 : (clone $base)->whereIn('ticket_status_id', $openStatusIds)->count();
        $resolved_requests = empty($resolvedStatusIds) ? 0 : (clone $base)->whereIn('ticket_status_id', $resolvedStatusIds)->count();

        return [
            'total_requests' => $total,
            'in_progress' => $in_progress + $open_requests,
            'open_requests' => $open_requests + $in_progress,
            'resolved_requests' => $resolved_requests,
        ];
    }

    /**
     * List ticket requests for a specific user (requestor). Paginated, optional trash.
     */
    public function listForUser(int $userId, int $perPage = 10, bool $trash = false): AnonymousResourceCollection
    {
        $baseQuery = TicketRequest::query()->where('user_id', $userId);
        $all = (clone $baseQuery)->count();
        $trashed = (clone $baseQuery)->onlyTrashed()->count();

        $query = TicketRequest::query()->where('user_id', $userId)->with(['ticketStatus', 'serviceType', 'ticketPriority', 'assignedTo', 'createdBy']);
        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%')
                    ->orWhere('contact_email', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('ticket_status_id')) {
            $query->where('ticket_status_id', request('ticket_status_id'));
        }

        $requestedOrder = (string) request('order', 'updated_at');
        $requestedSort = strtolower((string) request('sort', 'desc'));
        $sortDir = in_array($requestedSort, ['asc', 'desc'], true) ? $requestedSort : 'desc';

        $orderMap = [
            'id' => 'ticket_requests.id',
            'request_number' => 'ticket_requests.request_number',
            'contact_name' => 'ticket_requests.contact_name',
            'contact_email' => 'ticket_requests.contact_email',
            'created_at' => 'ticket_requests.created_at',
            'updated_at' => 'ticket_requests.updated_at',
            'service_type_name' => 'service_types.name',
            'ticket_status_label' => 'ticket_statuses.label',
            'ticket_priority_level' => 'ticket_priorities.level',
        ];
        $orderBy = $orderMap[$requestedOrder] ?? $orderMap['updated_at'];

        if ($requestedOrder === 'service_type_name') {
            $query->leftJoin('service_types', 'ticket_requests.service_type_id', '=', 'service_types.id')
                ->select('ticket_requests.*');
        } elseif ($requestedOrder === 'ticket_status_label') {
            $query->leftJoin('ticket_statuses', 'ticket_requests.ticket_status_id', '=', 'ticket_statuses.id')
                ->select('ticket_requests.*');
        } elseif ($requestedOrder === 'ticket_priority_level') {
            $query->leftJoin('ticket_priorities', 'ticket_requests.ticket_priority_id', '=', 'ticket_priorities.id')
                ->select('ticket_requests.*');
        }

        if ($requestedOrder === 'ticket_priority_level') {
            if ($sortDir === 'asc') {
                $query->orderByRaw('COALESCE(ticket_priorities.level, 999999) ASC');
            } else {
                $query->orderByRaw('COALESCE(ticket_priorities.level, 0) DESC');
            }
        } else {
            $query->orderBy($orderBy, $sortDir);
        }

        return TicketRequestResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'meta' => [
                'all' => $all,
                'trashed' => $trashed,
            ],
        ]);
    }

    /**
     * Show a single ticket request; ensure it belongs to the given user (for requestor).
     */
    public function showForUser(int $id, int $userId)
    {
        $model = TicketRequest::withTrashed()->where('user_id', $userId)->with(['ticketStatus', 'serviceType', 'sla', 'ticketPriority', 'user', 'assignedTo', 'createdBy'])->findOrFail($id);
        return TicketRequestResource::make($model);
    }
}
