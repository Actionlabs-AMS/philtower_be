<?php

namespace App\Services\Support;

use App\Http\Resources\SlaResource;
use App\Models\Support\Sla;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SlaService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new SlaResource(new Sla()), new Sla());
    }

    /**
     * Map request fields pause_on_onhold / pause_on_fe_visit to pause_conditions JSON.
     */
    private function mapPauseConditions(array $data): array
    {
        $data['pause_conditions'] = [
            'on_onhold' => $data['pause_on_onhold'] ?? false,
            'on_fe_visit' => $data['pause_on_fe_visit'] ?? false,
        ];
        unset($data['pause_on_onhold'], $data['pause_on_fe_visit']);
        return $data;
    }

    public function store(array $data)
    {
        $data = $this->mapPauseConditions($data);
        return parent::store($data);
    }

    public function update(array $data, int $id)
    {
        $data = $this->mapPauseConditions($data);
        return parent::update($data, $id);
    }

    /**
     * List SLAs (paginated). Optional trash view.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = Sla::query();
        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $search = request('search');
            $query->where('severity', 'like', '%' . $search . '%');
        }

        $orderField = request('order', 'severity');
        $sortDir = request('sort', 'asc');
        $query->orderBy($orderField, $sortDir);

        return SlaResource::collection(
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
        $model = Sla::withTrashed()->findOrFail($id);
        return SlaResource::make($model);
    }
}
