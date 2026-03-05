<?php

namespace App\Http\Controllers\Api\Support;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreSlaRequest;
use App\Http\Requests\UpdateSlaRequest;
use App\Services\Support\SlaService;
use App\Services\MessageService;

/**
 * SLA & Timing CRUD (Service Catalog). Soft deletes, bulk actions.
 */
class SlaController extends BaseController
{
    public function __construct(SlaService $slaService, MessageService $messageService)
    {
        parent::__construct($slaService, $messageService);
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

    public function store(StoreSlaRequest $request)
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

    public function update(UpdateSlaRequest $request, $id)
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

    public function bulkDelete(Request $request)
    {
        return parent::bulkDelete($request);
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

    public function bulkRestore(Request $request)
    {
        return parent::bulkRestore($request);
    }

    public function bulkForceDelete(Request $request)
    {
        return parent::bulkForceDelete($request);
    }
}
