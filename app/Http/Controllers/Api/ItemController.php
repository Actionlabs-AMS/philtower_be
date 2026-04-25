<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Services\ItemService;
use App\Services\MessageService;

class ItemController extends BaseController
{
    public function __construct(ItemService $service, MessageService $messageService)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $perPage = (int) request('per_page', 10);
            $trash = (bool) request('trash', false);
            return $this->service->list($perPage, $trash);
        } catch (\Throwable $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function store(StoreServiceTypeRequest $request)
    {
        try {
            $data = $request->validated();
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

    public function update(UpdateServiceTypeRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $resource = $this->service->update($data, (int) $id);
            return response($resource, 200);
        } catch (\Exception $e) {
            return $this->messageService->responseError($e);
        }
    }

    public function destroy($id)
    {
        return parent::destroy($id);
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

    /**
     * Retrieve all service types with pagination.
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
        $query = Item::query();

        // ✅ Soft delete filter
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

        // ✅ JSON subcategory filter (FIXED)
        if (request()->filled('subcategory_id')) {
            $subcategoryIds = request('subcategory_id');

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

        // 📄 Pagination
        $paginator = $query->paginate($perPage)->withQueryString();

        return ItemResource::collection($paginator);
    }

    private array $levelCache = [];

    private $map;
    public function getMap()
    {
        if (!$this->map) {
            $this->map = Item::select('id', 'subcategory_id')
                ->get()
                ->keyBy('id');
        }

        return $this->map;
    }
}
