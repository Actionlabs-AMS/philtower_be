<?php

namespace App\Services\Support;

use App\Http\Resources\TicketStatusResource;
use App\Models\Support\TicketStatus;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketStatusService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new TicketStatusResource(new TicketStatus()), new TicketStatus());
    }

    /**
     * List ticket statuses (paginated). Optional trash view.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = TicketStatus::query();
        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('label', 'like', '%' . $search . '%');
            });
        }

        $orderField = request('order', 'code');
        $sortDir = request('sort', 'asc');
        $query->orderBy($orderField, $sortDir);

        return TicketStatusResource::collection(
            $query->paginate($perPage)->withQueryString()
        )->additional([
            'meta' => [
                'all' => $all,
                'trashed' => $trashed,
            ],
        ]);
    }

    /**
     * Get single resource for show.
     */
    public function show(int $id)
    {
        $model = TicketStatus::withTrashed()->findOrFail($id);
        return TicketStatusResource::make($model);
    }
}
