<?php

namespace App\Services\Support;

use App\Http\Resources\TicketRequestResource;
use App\Models\Support\ServiceType;
use App\Models\Support\TicketRequest;
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
     * If service_type_id is set, use service type's approval flag; otherwise keep request value or 3.
     */
    protected function applyForApprovalFromServiceType(array $data): array
    {
        $serviceTypeId = $data['service_type_id'] ?? null;
        if ($serviceTypeId) {
            $serviceType = ServiceType::find($serviceTypeId);
            if ($serviceType) {
                $approval = $serviceType->approval;
                $data['for_approval'] = ($approval === true || $approval === 1) ? self::FOR_APPROVAL_YES : self::FOR_APPROVAL_NO;
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
        return parent::update($data, $id);
    }

    /**
     * List ticket requests (paginated). Optional trash view.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = TicketRequest::query()->with(['ticketStatus', 'serviceType']);
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

        $orderField = request('order', 'created_at');
        $sortDir = request('sort', 'desc');
        $query->orderBy($orderField, $sortDir);

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
}
