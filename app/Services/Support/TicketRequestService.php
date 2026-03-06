<?php

namespace App\Services\Support;

use App\Helpers\SlaHelper;
use App\Http\Resources\TicketRequestResource;
use App\Models\Support\ServiceType;
use App\Models\Support\TicketRequest;
use App\Models\Support\TicketStatus;
use App\Models\Support\TicketUpdate;
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

    public function store(array $data)
    {
        $data = $this->applyForApprovalFromServiceType($data);
        $data = $this->normalizeAttachmentMetadata($data);
        return parent::store($data);
    }

    public function update(array $data, int $id)
    {
        $data = $this->applyForApprovalFromServiceType($data);
        $data = $this->normalizeAttachmentMetadata($data);

        $model = TicketRequest::with('ticketStatus')->findOrFail($id);
        $oldStatusId = $model->ticket_status_id;
        $oldLabel = $model->ticketStatus?->label ?? 'Unknown';

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
        }

        return $out;
    }

    /**
     * List ticket requests (paginated). Optional trash view.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = TicketRequest::query()->with(['ticketStatus', 'serviceType', 'assignedTo']);
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
            // Relation display fields (require join)
            'service_type_name' => 'service_types.name',
            'ticket_status_label' => 'ticket_statuses.label',
        ];

        $orderBy = $orderMap[$requestedOrder] ?? $orderMap['created_at'];

        // Join related tables only when sorting by their display fields
        if ($requestedOrder === 'service_type_name') {
            $query->leftJoin('service_types', 'ticket_requests.service_type_id', '=', 'service_types.id')
                ->select('ticket_requests.*');
        } elseif ($requestedOrder === 'ticket_status_label') {
            $query->leftJoin('ticket_statuses', 'ticket_requests.ticket_status_id', '=', 'ticket_statuses.id')
                ->select('ticket_requests.*');
        }

        $query->orderBy($orderBy, $sortDir);

        return TicketRequestResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'meta' => [
                'all' => $all,
                'trashed' => $trashed,
            ],
        ]);
    }

    public function show(int $id)
    {
        $model = TicketRequest::withTrashed()->with(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo'])->findOrFail($id);
        return TicketRequestResource::make($model);
    }

    /**
     * Set ticket status to approved (for_approval=1 tickets).
     */
    public function approve(int $id)
    {
        $status = \App\Models\Support\TicketStatus::where('code', 'approved')->firstOrFail();
        $model = TicketRequest::findOrFail($id);
        $model->ticket_status_id = $status->id;
        $model->save();
        return TicketRequestResource::make($model->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo']));
    }

    /**
     * Set ticket status to rejected (cancel approval).
     */
    public function reject(int $id)
    {
        $status = \App\Models\Support\TicketStatus::where('code', 'rejected')->firstOrFail();
        $model = TicketRequest::findOrFail($id);
        $model->ticket_status_id = $status->id;
        $model->save();
        return TicketRequestResource::make($model->load(['ticketStatus', 'serviceType', 'sla', 'user', 'assignedTo']));
    }
}
