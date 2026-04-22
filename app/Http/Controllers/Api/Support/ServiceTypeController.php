<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreServiceTypeRequest;
use App\Http\Requests\UpdateServiceTypeRequest;
use App\Models\Support\ServiceType;
use App\Services\MessageService;
use App\Services\Support\ServiceTypeService;
use App\Http\Resources\ServiceTypeResource;

class ServiceTypeController extends BaseController
{
    public function __construct(ServiceTypeService $serviceTypeService, MessageService $messageService)
    {
        parent::__construct($serviceTypeService, $messageService);
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
     * Get all active service types (for options/dropdowns).
     */
    public function getServiceTypes()
    {
        $types = ServiceType::whereNull('parent_id')
            ->where('active', 1)
            ->with('children') // load children
            ->get();

        return ServiceTypeResource::collection($types);
    }

    public function getSubServiceTypes($id)
    {
        return ServiceTypeResource::collection(ServiceType::query()->where('active', 1)->where('parent_id', $id)->orderBy('id', 'asc')->get());
    }

    /**
     * Get service types for request-type options.
     */
    public function getRequestTypes()
    {
        $types = ServiceType::where('active', true)
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'parent_id']);
        return response()->json($types);
    }
}
