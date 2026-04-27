<?php

namespace App\Services\Support;

use App\Http\Resources\ServiceTypeResource;
use App\Models\Support\ServiceType;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceTypeService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ServiceTypeResource(new ServiceType()), new ServiceType());
    }

    /**
     * Retrieve all service types with pagination.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        $query = ServiceType::query()->with('parent');

        if ($trash) {
            $query->onlyTrashed();
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (request()->has('active') && request('active') !== '') {
            $query->where('active', (bool) request('active'));
        }

        if (request()->filled('parent_id')) {
            $parentId = request('parent_id');

            if ($parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $parentId);
            }
        }

        $orderField = request('order', 'name');
        $sortDir = request('sort', 'asc');
        $query->orderBy($orderField, $sortDir);

        $paginator = $query->paginate($perPage)->withQueryString();

        // IMPORTANT: build map from FULL dataset (not paginated subset)
        $map = $this->getMap();

        $paginator->getCollection()->transform(function ($item) use ($map) {
            $item->level = $this->computeLevel($item, $map);
            return $item;
        });

        return ServiceTypeResource::collection($paginator)
            ->additional([
                'meta' => [
                    'all' => $all,
                    'trashed' => $trashed,
                ],
            ]);
    }

    /**
     * Get single resource for show (return as resource so JSON is consistent).
     */
    public function show(int $id)
    {
        $model = ServiceType::with('parent')->findOrFail($id);
        return ServiceTypeResource::make($model);
    }

    private array $levelCache = [];

    public function computeLevel($node, $map = null): int
    {
        if (!$node->id) return 0;

        // ✅ cache check
        if (isset($this->levelCache[$node->id])) {
            return $this->levelCache[$node->id];
        }

        if (!$node->parent_id) {
            return $this->levelCache[$node->id] = 0;
        }

        if ($map && isset($map[$node->parent_id])) {
            return $this->levelCache[$node->id] =
                1 + $this->computeLevel($map[$node->parent_id], $map);
        }

        $parent = $node->parent;

        return $this->levelCache[$node->id] =
            ($parent ? 1 + $this->computeLevel($parent) : 0);
    }

    private $map;
    public function getMap()
    {
        if (!$this->map) {
            $this->map = ServiceType::select('id', 'parent_id')
                ->get()
                ->keyBy('id');
        }

        return $this->map;
    }
}
