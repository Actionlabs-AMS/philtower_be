<?php

namespace App\Services;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new ItemResource(new Item()), new Item());
    }

    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $all = $this->getTotalCount();
        $trashed = $this->getTrashedCount();

        // ✅ FIX: use Item model
        $query = Item::query();

        if ($trash) {
            $query->onlyTrashed();
        }

        // 🔍 Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // ✅ Active filter
        if (request()->has('active') && request('active') !== '') {
            $query->where('active', (bool) request('active'));
        }

        // ✅ JSON subcategory filter
        if (request()->filled('subcategory_id')) {
            $subcategoryIds = request('subcategory_id');

            // Ensure it's an array
            if (!is_array($subcategoryIds)) {
                $subcategoryIds = [$subcategoryIds];
            }

            $query->where(function ($q) use ($subcategoryIds) {
                foreach ($subcategoryIds as $id) {
                    $q->orWhereJsonContains('subcategory_id', (int) $id);
                }
            });
        }

        // 🔽 Sorting
        $orderField = request('order', 'name');
        $sortDir = request('sort', 'asc');
        $query->orderBy($orderField, $sortDir);

        $paginator = $query->paginate($perPage)->withQueryString();

        return ItemResource::collection($paginator)
            ->additional([
                'meta' => [
                    'all' => $all,
                    'trashed' => $trashed,
                ],
            ]);
    }

    /**
     * Show single item
     */
    public function show(int $id)
    {
        $model = Item::findOrFail($id);
        return ItemResource::make($model);
    }
}