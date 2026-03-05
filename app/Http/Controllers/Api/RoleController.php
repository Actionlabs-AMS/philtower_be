<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Services\MessageService;
use App\Services\RoleService;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Http\Resources\RoleResource;

/**
 * @OA\Tag(
 *     name="Role Management",
 *     description="API endpoints for role and permission management"
 * )
 */
class RoleController extends BaseController
{
  public function __construct(RoleService $roleService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($roleService, $messageService);
  }

  /**
   * Display a listing of roles.
   * 
   * @OA\Get(
   *     path="/api/user-management/roles",
   *     summary="Get list of roles",
   *     tags={"Role Management"},
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
   *         description="List of roles retrieved successfully",
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
   * Display the specified role.
   * 
   * @OA\Get(
   *     path="/api/user-management/roles/{id}",
   *     summary="Get a specific role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found.")
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
   * Remove the specified role from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/user-management/roles/{id}",
   *     summary="Delete a role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found.")
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
   * Bulk delete multiple roles.
   * 
   * @OA\Post(
   *     path="/api/user-management/roles/bulk/delete",
   *     summary="Bulk delete multiple roles",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of role IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Roles deleted successfully",
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
   * Get trashed roles.
   * 
   * @OA\Get(
   *     path="/api/user-management/archived/roles",
   *     summary="Get trashed roles",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed roles retrieved successfully",
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
   * Restore a trashed role.
   * 
   * @OA\Patch(
   *     path="/api/user-management/archived/roles/restore/{id}",
   *     summary="Restore a trashed role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found.")
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
   * Bulk restore multiple trashed roles.
   * 
   * @OA\Post(
   *     path="/api/user-management/roles/bulk/restore",
   *     summary="Bulk restore multiple trashed roles",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of role IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Roles restored successfully",
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
   * Permanently delete a role.
   * 
   * @OA\Delete(
   *     path="/api/user-management/archived/roles/{id}",
   *     summary="Permanently delete a role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found.")
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
   * Bulk permanently delete multiple roles.
   * 
   * @OA\Post(
   *     path="/api/user-management/roles/bulk/force-delete",
   *     summary="Bulk permanently delete multiple roles",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of role IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Roles permanently deleted successfully",
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
   *     path="/api/user-management/roles",
   *     summary="Create a new role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name"},
   *             @OA\Property(property="name", type="string", example="Editor", description="Role name"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Role active status"),
   *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"create", "read", "update"}, description="Array of permission names")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Role created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role has been created successfully."),
   *             @OA\Property(property="role", type="object")
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
  public function store(RoleRequest $request)
  {
    try {
      $data = $request->all();
      
      // Save the role
      $role = new Role();
      $role->name = $data['name'];
      $role->active = $data['active'] ?? true;
      $role->save();

      // Fetch permissions once to optimize lookup
      $permissions = Permission::pluck('id', 'name')->toArray();

      $dataToInsert = [];

      // Iterate over the permissions data
      if (isset($data['permissions']) && is_array($data['permissions'])) {
        foreach ($data['permissions'] as $parent_navigation_id => $navigations) {
          $role_id = $role->id; // Use the newly created role's ID

          if (!is_array($navigations)) {
            \Log::warning('RoleController::store - Invalid navigations structure', [
              'parent_navigation_id' => $parent_navigation_id,
              'navigations' => $navigations
            ]);
            continue;
          }

          foreach ($navigations as $navigation_id => $permissions_data) {
            if (!is_array($permissions_data)) {
              \Log::warning('RoleController::store - Invalid permissions_data structure', [
                'navigation_id' => $navigation_id,
                'permissions_data' => $permissions_data
              ]);
              continue;
            }

            foreach ($permissions_data as $permission_name => $allowed) {
              if ($allowed && isset($permissions[$permission_name])) {
                $permission_id = $permissions[$permission_name];

                $dataToInsert[] = [
                  'role_id' => $role_id,
                  'navigation_id' => (int)$navigation_id,
                  'permission_id' => $permission_id,
                  'allowed' => true,
                  'created_at' => now(),
                  'updated_at' => now(),
                ];
              }
            }
          }
        }
      }

      // Insert all records at once if any data to insert
      if (!empty($dataToInsert)) {
        RolePermission::insert($dataToInsert);
      }
    
      // Return the created role resource
      return response(new RoleResource($role), 201);
    } catch (\Exception $e) {
      \Log::error('RoleController::store - Error creating role', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $request->all()
      ]);
      return $this->messageService->responseError();
    }
  }

  /**
   * Update a resource in storage.
   * 
   * @OA\Put(
   *     path="/api/user-management/roles/{id}",
   *     summary="Update a role",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Role ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name"},
   *             @OA\Property(property="name", type="string", example="Editor", description="Role name"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Role active status"),
   *             @OA\Property(property="permissions", type="object", description="Role permissions object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Role updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role has been updated successfully."),
   *             @OA\Property(property="role", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Role not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Role not found.")
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
  public function update(RoleRequest $request, $id)
  {
    try {
      $data = $request->all();

      // Find the existing role by ID
      $role = Role::findOrFail($id);

      // Update the role's basic information
      $role->name = $data['name'];
      $role->active = $data['active'] ?? true;
      $role->save();

      // Fetch the existing permissions
      $permissions = Permission::pluck('id', 'name')->toArray();

      // Remove any existing permissions for the role
      RolePermission::where('role_id', $id)->delete();

      // Prepare new permissions data for insertion
      $dataToInsert = [];

      if (isset($data['permissions']) && is_array($data['permissions'])) {
        foreach ($data['permissions'] as $parent_navigation_id => $navigations) {
          $role_id = $role->id; // Use the updated role's ID

          if (!is_array($navigations)) {
            \Log::warning('RoleController::update - Invalid navigations structure', [
              'role_id' => $role_id,
              'parent_navigation_id' => $parent_navigation_id,
              'navigations' => $navigations
            ]);
            continue;
          }

          foreach ($navigations as $navigation_id => $permissions_data) {
            if (!is_array($permissions_data)) {
              \Log::warning('RoleController::update - Invalid permissions_data structure', [
                'navigation_id' => $navigation_id,
                'permissions_data' => $permissions_data
              ]);
              continue;
            }

            foreach ($permissions_data as $permission_name => $allowed) {
              // Only insert if the permission is allowed and exists in the database
              if ($allowed && isset($permissions[$permission_name])) {
                $permission_id = $permissions[$permission_name];

                $dataToInsert[] = [
                  'role_id' => $role_id,
                  'navigation_id' => (int)$navigation_id,
                  'permission_id' => $permission_id,
                  'allowed' => true,
                  'created_at' => now(),
                  'updated_at' => now(),
                ];
              }
            }
          }
        }
      }

      // Insert new permissions data if there is any
      if (!empty($dataToInsert)) {
        RolePermission::insert($dataToInsert);
      }

      // Return the updated resource (role)
      return response(new RoleResource($role), 200);
    } catch (\Exception $e) {
      \Log::error('RoleController::update - Error updating role', [
        'role_id' => $id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $request->all()
      ]);
      // Handle exception and return error response
      return $this->messageService->responseError();
    }
  }

  /**
   * Get all roles resource.
   * 
   * @OA\Get(
   *     path="/api/options/roles",
   *     summary="Get all roles for dropdown",
   *     tags={"Role Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Roles retrieved successfully",
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
  public function getRoles() 
  {
    try {
      return $this->service->getRoles();
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
