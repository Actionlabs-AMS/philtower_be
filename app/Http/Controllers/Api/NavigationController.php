<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\NavigationRequest;
use App\Services\MessageService;
use App\Services\NavigationService;

/**
 * @OA\Tag(
 *     name="Navigation Management",
 *     description="API endpoints for navigation management"
 * )
 */
class NavigationController extends BaseController
{
  public function __construct(NavigationService $navigationService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($navigationService, $messageService);
  }

  /**
   * Display a listing of navigations.
   * 
   * @OA\Get(
   *     path="/api/system-settings/navigation",
   *     summary="Get list of navigations",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Items per page",
   *         @OA\Schema(type="integer", example=10)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="List of navigations retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
   *             @OA\Property(property="meta", type="object"),
   *             @OA\Property(property="links", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function index()
  {
    return parent::index();
  }

  /**
   * Display the specified navigation.
   * 
   * @OA\Get(
   *     path="/api/system-settings/navigation/{id}",
   *     summary="Get a specific navigation",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function show($id)
  {
    return parent::show($id);
  }

  /**
   * Remove the specified navigation from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/system-settings/navigation/{id}",
   *     summary="Delete a navigation",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function destroy($id)
  {
    return parent::destroy($id);
  }

  /**
   * Bulk delete multiple navigations.
   * 
   * @OA\Post(
   *     path="/api/system-settings/navigation/bulk/delete",
   *     summary="Bulk delete multiple navigations",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of navigation IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigations deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkDelete(Request $request)
  {
    return parent::bulkDelete($request);
  }

  /**
   * Get trashed navigations.
   * 
   * @OA\Get(
   *     path="/api/system-settings/archived/navigation",
   *     summary="Get trashed navigations",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed navigations retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getTrashed()
  {
    return parent::getTrashed();
  }

  /**
   * Restore a trashed navigation.
   * 
   * @OA\Patch(
   *     path="/api/system-settings/archived/navigation/restore/{id}",
   *     summary="Restore a trashed navigation",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function restore($id)
  {
    return parent::restore($id);
  }

  /**
   * Bulk restore multiple trashed navigations.
   * 
   * @OA\Post(
   *     path="/api/system-settings/navigation/bulk/restore",
   *     summary="Bulk restore multiple trashed navigations",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of navigation IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigations restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been restored.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkRestore(Request $request)
  {
    return parent::bulkRestore($request);
  }

  /**
   * Permanently delete a navigation.
   * 
   * @OA\Delete(
   *     path="/api/system-settings/archived/navigation/{id}",
   *     summary="Permanently delete a navigation",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function forceDelete($id)
  {
    return parent::forceDelete($id);
  }

  /**
   * Bulk permanently delete multiple navigations.
   * 
   * @OA\Post(
   *     path="/api/system-settings/navigation/bulk/force-delete",
   *     summary="Bulk permanently delete multiple navigations",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of navigation IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigations permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resources have been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function bulkForceDelete(Request $request)
  {
    return parent::bulkForceDelete($request);
  }

  /**
   * Store a newly created resource in storage.
   * 
   * @OA\Post(
   *     path="/api/system-settings/navigation",
   *     summary="Create a new navigation item",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name", "route"},
   *             @OA\Property(property="name", type="string", example="Dashboard", description="Navigation name"),
   *             @OA\Property(property="route", type="string", example="/dashboard", description="Navigation route"),
   *             @OA\Property(property="icon", type="string", example="fas fa-home", description="Navigation icon"),
   *             @OA\Property(property="parent_id", type="integer", example=null, description="Parent navigation ID"),
   *             @OA\Property(property="order", type="integer", example=1, description="Navigation order"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Navigation active status")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Navigation created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation has been created successfully."),
   *             @OA\Property(property="navigation", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function store(NavigationRequest $request)
  {
    try {
      $data = $request->all();
      $resource = $this->service->store($data);
      return response($resource, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Update a resource in storage.
   * 
   * @OA\Put(
   *     path="/api/system-settings/navigation/{id}",
   *     summary="Update a navigation item",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="name", type="string", example="Dashboard", description="Navigation name"),
   *             @OA\Property(property="route", type="string", example="/dashboard", description="Navigation route"),
   *             @OA\Property(property="icon", type="string", example="fas fa-home", description="Navigation icon"),
   *             @OA\Property(property="parent_id", type="integer", example=null, description="Parent navigation ID"),
   *             @OA\Property(property="order", type="integer", example=1, description="Navigation order"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Navigation active status")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Navigation updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation has been updated successfully."),
   *             @OA\Property(property="navigation", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid."),
   *             @OA\Property(property="errors", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function update(NavigationRequest $request, $id)
  {
    try {
      $data = $request->all();
      $resource = $this->service->update($data, $id);
      return response($resource, 200);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get all navigations for dropdown/options.
   * 
   * @OA\Get(
   *     path="/api/options/navigations",
   *     summary="Get all navigations for dropdown",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Navigations retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getNavigations() 
  {
    try {
			return $this->service->navigations();
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get sub-navigations for a specific navigation.
   * 
   * @OA\Get(
   *     path="/api/options/navigations/{id}",
   *     summary="Get sub-navigations for a specific navigation",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Parent navigation ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Sub-navigations retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Navigation not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Navigation not found.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getSubNavigations($id) 
  {
    try {
			return $this->service->subNavigations($id);
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get all available routes.
   * 
   * @OA\Get(
   *     path="/api/options/routes",
   *     summary="Get all available routes",
   *     tags={"Navigation Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Routes retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthenticated",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthenticated.")
   *         )
   *     )
   * )
   */
  public function getRoutes() 
  {
    try {
			return $this->service->getRoutes();
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
