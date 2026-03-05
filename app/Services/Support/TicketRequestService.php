<?php

namespace App\Services\Support;

use App\Http\Resources\TicketRequestResource;
use App\Models\Support\TicketRequest;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketRequestService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TicketRequestResource(new TicketRequest()), new TicketRequest());
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
