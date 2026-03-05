<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MessageService;

class BaseController extends Controller
{
  protected $service;
  protected $messageService;

  // Inject the common services into the BaseController constructor
  public function __construct($service, MessageService $messageService)
  {
    $this->service = $service;
    $this->messageService = $messageService;
  }

  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $items = $this->service->list();
      return $items;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle showing resources
  public function show($id)
  {
    try {
      $item = $this->service->show($id);
      return $item;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle destroying resources
  public function destroy($id)
  {
    try {
      // Cast to int since route parameters are strings
      $id = (int) $id;
      if ($id <= 0) {
        return response(['error' => 'Invalid ID'], 404);
      }
      $this->service->destroy($id);
      return response(['message' => 'Resource has been moved to trash.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk deleting resources
  public function bulkDelete(Request $request)
  {
    try {
      $this->service->bulkDelete($request->ids);
      return response(['message' => 'Resources have been deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle trashed resources
  public function getTrashed() 
  {
    try {
      $items = $this->service->list(10, true);
      return $items;
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle restoring resources
  public function restore($id)
  {
    try {
      $item = $this->service->restore($id);
      return response([
        'message' => 'Resource has been restored.',
        'resource' => $item
      ], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk restoring resources
  public function bulkRestore(Request $request)
  {
    try {
      $this->service->bulkRestore($request->ids);
      return response(['message' => 'Resources have been restored.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle force deleting resources
  public function forceDelete($id)
  {
    try {
      $this->service->forceDelete($id);
      return response(['message' => 'Resource has been permanently deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  // Common method to handle bulk force deleting resources
  public function bulkForceDelete(Request $request)
  {
    try {
      $this->service->bulkForceDelete($request->ids);
      return response(['message' => 'Resources have been permanently deleted.'], 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
