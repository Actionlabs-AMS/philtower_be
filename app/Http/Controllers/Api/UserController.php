<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\ImportUsersRequest;
use App\Helpers\PasswordHelper;
use App\Services\UserService;
use App\Services\MessageService;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="API endpoints for user management operations"
 * )
 */
class UserController extends BaseController
{
  public function __construct(UserService $userService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($userService, $messageService);
  }

  /**
   * Display a listing of users.
   * 
   * @OA\Get(
   *     path="/api/user-management/users",
   *     summary="Get list of users",
   *     tags={"User Management"},
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
   *         description="List of users retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
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
   * Dedicated endpoint for users who have the Approver role only.
   *
   * @OA\Get(
   *   path="/api/user-management/users/approvers",
   *   summary="Get approvers only",
   *   tags={"User Management"},
   *   security={{"sanctum": {}}},
   *   @OA\Parameter(
   *     name="per_page",
   *     in="query",
   *     description="Items per page",
   *     required=false,
   *     @OA\Schema(type="integer", example=1000)
   *   ),
   *   @OA\Response(
   *     response=200,
   *     description="Approvers retrieved successfully"
   *   )
   * )
   */
  public function approvers(Request $request)
  {
    // UserService::list uses request('role_name') to filter by role.
    $request->merge(['role_name' => 'Approver']);
    $perPage = (int) ($request->input('per_page', 10));

    return $this->service->list($perPage, false);
  }

  /**
   * Display the specified user.
   * 
   * @OA\Get(
   *     path="/api/user-management/users/{id}",
   *     summary="Get a specific user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", ref="#/components/schemas/User")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User not found.")
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
   * Remove the specified user from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/user-management/users/{id}",
   *     summary="Delete a user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User not found.")
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
   * Bulk delete multiple users.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/bulk/delete",
   *     summary="Bulk delete multiple users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of user IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Users deleted successfully",
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
   * Get trashed users.
   * 
   * @OA\Get(
   *     path="/api/user-management/archived/users",
   *     summary="Get trashed users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed users retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
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
   * Restore a trashed user.
   * 
   * @OA\Patch(
   *     path="/api/user-management/archived/users/restore/{id}",
   *     summary="Restore a trashed user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", ref="#/components/schemas/User")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User not found.")
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
   * Bulk restore multiple trashed users.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/bulk/restore",
   *     summary="Bulk restore multiple trashed users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of user IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Users restored successfully",
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
   * Permanently delete a user.
   * 
   * @OA\Delete(
   *     path="/api/user-management/archived/users/{id}",
   *     summary="Permanently delete a user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User not found.")
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
   * Bulk permanently delete multiple users.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/bulk/force-delete",
   *     summary="Bulk permanently delete multiple users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of user IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Users permanently deleted successfully",
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
   * Create a new user.
   * 
   * @OA\Post(
   *     path="/api/user-management/users",
   *     summary="Create a new user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"user_login", "user_email", "user_pass"},
   *             @OA\Property(property="user_login", type="string", example="johndoe", description="Username"),
   *             @OA\Property(property="user_email", type="string", format="email", example="john@example.com", description="User email"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="SecurePass123!", description="User password"),
   *             @OA\Property(property="first_name", type="string", example="John", description="User first name"),
   *             @OA\Property(property="last_name", type="string", example="Doe", description="User last name"),
   *             @OA\Property(property="user_role", type="string", example="admin", description="User role")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="User created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User has been created successfully."),
   *             @OA\Property(property="user", ref="#/components/schemas/User")
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
  public function store(StoreUserRequest $request)
  {
    try {
      $data = $request->validated();

      // Debug: Log incoming request data
      \Log::info('[UserController] Store request:', [
        'validated' => $data,
        'all_request' => $request->all(),
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'employee_id' => $request->employee_id,
        'position' => $request->position,
      ]);

      $salt = PasswordHelper::generateSalt();
      $password = PasswordHelper::generatePassword($salt, $data['user_pass']);
      $activation_key = PasswordHelper::generateSalt();

      $data = [
        'user_login' => $data['user_login'],
        'user_email' => $data['user_email'],
        'user_salt' => $salt,
        'user_pass' => $password,
        'user_status' => 1,
        'user_activation_key' => $activation_key,
      ];

      $meta_details = [];
      if(isset($request->first_name))
        $meta_details['first_name'] = $request->first_name;
        
      if(isset($request->last_name))
        $meta_details['last_name'] = $request->last_name;
      
      if(isset($request->employee_id))
        $meta_details['employee_id'] = $request->employee_id;
      
      if(isset($request->position))
        $meta_details['position'] = $request->position;
    
      if (isset($request->can_view_all)) {
        $meta_details['can_view_all'] = $request->boolean('can_view_all') ? '1' : '0';
      }
      if ($request->has('approver_id')) {
        $meta_details['approver_id'] = $request->filled('approver_id') ? (string) ((int) $request->approver_id) : null;
      }

      if(isset($request->user_role)) {
        // Handle user_role as object or array - convert to JSON string for user_meta
        if (is_array($request->user_role) || is_object($request->user_role)) {
          // Extract role_id from the object/array
          $roleId = is_array($request->user_role) ? ($request->user_role['id'] ?? null) : ($request->user_role->id ?? null);
          if ($roleId) {
            $data['role_id'] = $roleId;
          }
          // Convert to JSON string for user_meta
          $meta_details['user_role'] = json_encode($request->user_role);
        } else {
          // Already a string, use as is
          $meta_details['user_role'] = $request->user_role;
        }
      }

      // Debug: Log meta details being saved
      \Log::info('[UserController] Meta details to save:', $meta_details);

      $user = $this->service->storeWithMeta($data, $meta_details);
      
      // Debug: Log saved user
      \Log::info('[UserController] User created:', [
        'user_id' => $user->id,
        'user_login' => $user->user_login,
        'first_name_meta' => $user->getMeta('first_name'),
        'last_name_meta' => $user->getMeta('last_name'),
        'employee_id_meta' => $user->getMeta('employee_id'),
        'position_meta' => $user->getMeta('position'),
      ]);
      
      return response($user, 201);
    } catch (\Exception $e) {
      \Log::error('[UserController] Store error:', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return $this->messageService->responseError();
    }
  }

  /**
   * Update a user.
   * 
   * @OA\Put(
   *     path="/api/user-management/users/{id}",
   *     summary="Update a user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="User ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="user_login", type="string", example="johndoe", description="Username"),
   *             @OA\Property(property="user_email", type="string", format="email", example="john@example.com", description="User email"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="NewSecurePass123!", description="New password (optional)"),
   *             @OA\Property(property="user_status", type="integer", example=1, description="User status (0=inactive, 1=active, 2=suspended)"),
   *             @OA\Property(property="first_name", type="string", example="John", description="User first name"),
   *             @OA\Property(property="last_name", type="string", example="Doe", description="User last name"),
   *             @OA\Property(property="user_role", type="string", example="admin", description="User role")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="User updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User has been updated successfully."),
   *             @OA\Property(property="user", ref="#/components/schemas/User")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="User not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User not found.")
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
  public function update(UpdateUserRequest $request, Int $id)
  {
    // try {
      $data = $request->validated();
      $user = User::findOrFail($id);

      // Debug: Log incoming request data
      \Log::info('[UserController] Update request:', [
        'user_id' => $id,
        'validated' => $data,
        'all_request' => $request->all(),
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'employee_id' => $request->employee_id,
        'position' => $request->position,
      ]);

      $upData = [
        'user_login' => $request->user_login,
        'user_email' => $request->user_email,
        'user_status' => $request->user_status,
      ];

      if (isset($data['user_pass'])) {
        $salt = $user->user_salt;
        $upData['user_pass'] = PasswordHelper::generatePassword($salt, request('user_pass'));
      }

      $meta_details = [];
      if(isset($request->first_name))
        $meta_details['first_name'] = $request->first_name;
        
      if(isset($request->last_name))
        $meta_details['last_name'] = $request->last_name;
      
      if(isset($request->employee_id))
        $meta_details['employee_id'] = $request->employee_id;
      
      if(isset($request->position))
        $meta_details['position'] = $request->position;
    
      if (isset($request->can_view_all)) {
        $meta_details['can_view_all'] = $request->boolean('can_view_all') ? '1' : '0';
      }
      if ($request->has('approver_id')) {
        $meta_details['approver_id'] = $request->filled('approver_id') ? (string) ((int) $request->approver_id) : null;
      }

      if(isset($request->user_role)) {
        // Handle user_role as object or array - convert to JSON string for user_meta
        if (is_array($request->user_role) || is_object($request->user_role)) {
          // Extract role_id from the object/array
          $roleId = is_array($request->user_role) ? ($request->user_role['id'] ?? null) : ($request->user_role->id ?? null);
          if ($roleId) {
            $upData['role_id'] = $roleId;
          }
          // Convert to JSON string for user_meta
          $meta_details['user_role'] = json_encode($request->user_role);
        } else {
          // Already a string, use as is
          $meta_details['user_role'] = $request->user_role;
        }
      }

      // Debug: Log meta details being saved
      \Log::info('[UserController] Meta details to update:', $meta_details);

      $user = $this->service->updateWithMeta($upData, $meta_details, $user);

      // Debug: Log updated user
      \Log::info('[UserController] User updated:', [
        'user_id' => $user->id,
        'user_login' => $user->user_login,
        'first_name_meta' => $user->getMeta('first_name'),
        'last_name_meta' => $user->getMeta('last_name'),
        'employee_id_meta' => $user->getMeta('employee_id'),
        'position_meta' => $user->getMeta('position'),
      ]);

      return response($user, 201);
    // } catch (\Exception $e) {
    //   \Log::error('[UserController] Update error:', [
    //     'user_id' => $id,
    //     'message' => $e->getMessage(),
    //     'trace' => $e->getTraceAsString(),
    //   ]);
    //   return $this->messageService->responseError();
    // }
  }

  /**
   * Bulk change passwords for multiple users.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/bulk/password",
   *     summary="Bulk change passwords for multiple users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of user IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Passwords changed successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Passwords have been changed successfully.")
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
  public function bulkChangePassword(Request $request) 
  {
    try {
      $this->service->bulkChangePassword($request->ids);
      $message = 'Temporary password has been sent.';
      return response(compact('message'));
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Bulk change roles for multiple users.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/bulk/role",
   *     summary="Bulk change roles for multiple users",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids", "role"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of user IDs"),
   *             @OA\Property(property="role", type="string", example="editor", description="New role to assign")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Roles changed successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User/s role has been change.")
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
  public function bulkChangeRole(Request $request) 
  {
    try {
      $this->service->bulkChangeRole($request->ids, $request->role);
      $message = 'User/s role has been change.';
      return response(compact('message'));
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Update user profile.
   * 
   * @OA\Post(
   *     path="/api/profile",
   *     summary="Update user profile",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="user_login", type="string", example="johndoe", description="Username"),
   *             @OA\Property(property="user_email", type="string", format="email", example="john@example.com", description="User email"),
   *             @OA\Property(property="user_pass", type="string", format="password", example="NewSecurePass123!", description="New password (optional)"),
   *             @OA\Property(property="first_name", type="string", example="John", description="User first name"),
   *             @OA\Property(property="last_name", type="string", example="Doe", description="User last name"),
   *             @OA\Property(property="nickname", type="string", example="Johnny", description="User nickname"),
   *             @OA\Property(property="biography", type="string", example="Software developer", description="User biography"),
   *             @OA\Property(property="theme", type="string", example="dark", description="User theme preference"),
   *             @OA\Property(property="attachment_file", type="string", example="path/to/file.jpg", description="Profile attachment file"),
   *             @OA\Property(property="user_role", type="string", example="admin", description="User role information")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Profile updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Profile has been updated successfully."),
   *             @OA\Property(property="user", ref="#/components/schemas/User")
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
  public function updateProfile(ProfileRequest $request) 
  {
    try {
      $data = $request->all();
      $user = Auth::user();

      $upData = [
        'user_login' => $data['user_login'],
        'user_email' => $data['user_email'],
      ];

      if (isset($data['user_pass'])) {
        $salt = $user->user_salt;
        $upData['user_pass'] = PasswordHelper::generatePassword($salt, $data['user_pass']);
      }

      $meta_details = [];
      if(isset($data['first_name']))
        $meta_details['first_name'] = $data['first_name'];
        
      if(isset($data['last_name']))
        $meta_details['last_name'] = $data['last_name'];
    
      if(isset($data['nickname']))
        $meta_details['nickname'] = $data['nickname'];

      if(isset($data['biography']))
        $meta_details['biography'] = $data['biography'];

      if(isset($data['theme']))
        $meta_details['theme'] = $data['theme'];

      if(isset($data['attachment_file'])) {
        $attachment_file = json_decode($data['attachment_file']);

        $meta_details['attachment_metadata'] = json_encode($attachment_file);
        $meta_details['attachment_file'] = $attachment_file->file_url;  
      }

      if(isset($data['user_role'])) {
        $user_role = json_decode($data['user_role']);
        $meta_details['user_role'] = json_encode($user_role);
      }

      $user = $this->service->updateWithMeta($upData, $meta_details, $user);

      return response($user, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get current authenticated user.
   * 
   * @OA\Get(
   *     path="/api/user/me",
   *     summary="Get current authenticated user",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="User retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", ref="#/components/schemas/User")
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
  public function getUser(Request $request) 
	{
    try {
      $user = $request->user();
      return new UserResource($user);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
	}

  /**
   * Import users from CSV file.
   * 
   * @OA\Post(
   *     path="/api/user-management/users/import",
   *     summary="Import users from CSV file",
   *     tags={"User Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\MediaType(
   *             mediaType="multipart/form-data",
   *             @OA\Schema(
   *                 @OA\Property(
   *                     property="file",
   *                     type="string",
   *                     format="binary",
   *                     description="CSV file containing user data"
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Users imported successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="imported", type="integer", example=10),
   *             @OA\Property(property="skipped", type="integer", example=2),
   *             @OA\Property(property="errors", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The file field is required.")
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
  public function import(ImportUsersRequest $request)
  {
    try {

      $file = $request->file('file');
      if (!$file || !$file->isValid()) {
        return response()->json([
          'message' => 'Invalid file upload',
          'errors' => ['file' => ['The uploaded file is invalid.']]
        ], 422);
      }

      $extension = strtolower($file->getClientOriginalExtension());
      $imported = 0;
      $skipped = 0;
      $errors = [];
      $totalProcessed = 0;

      $parseStatus = function ($statusValue): int {
        if ($statusValue === null || $statusValue === '') {
          return 1;
        }

        $normalized = strtolower(trim((string) $statusValue));
        if (is_numeric($normalized)) {
          $statusCode = (int) $normalized;
          return in_array($statusCode, [0, 1, 2], true) ? $statusCode : 1;
        }

        if (in_array($normalized, ['active', 'regular'], true)) {
          return 1;
        }

        if ($normalized === 'suspended') {
          return 2;
        }

        return 0;
      };

      $normalizeLogin = function (string $value): string {
        $clean = preg_replace('/[^a-z0-9._-]/i', '', strtolower(trim($value)));
        return trim((string) $clean, '.-_');
      };

      $createUserFromData = function (array $data, int $rowNumber) use (&$imported, &$skipped, &$errors, $parseStatus, $normalizeLogin) {
        $email = trim((string) ($data['user_email'] ?? ''));
        $username = trim((string) ($data['user_login'] ?? ''));

        if ($username === '' && $email !== '') {
          $emailPrefix = explode('@', $email)[0] ?? '';
          $username = $normalizeLogin($emailPrefix);
        }

        if ($username === '' && (!empty($data['first_name']) || !empty($data['last_name']))) {
          $fallback = trim((string) (($data['first_name'] ?? '') . '.' . ($data['last_name'] ?? '')), '.');
          $username = $normalizeLogin($fallback);
        }

        $data['user_login'] = $username;
        $data['user_email'] = $email;

        if (empty($data['user_login']) || empty($data['user_email'])) {
          $skipped++;
          $errors[] = [
            'row' => $rowNumber,
            'message' => 'Missing required fields: Username and Email are required',
            'data' => $data
          ];
          return;
        }

        $existingUser = User::where('user_login', $data['user_login'])
          ->orWhere('user_email', $data['user_email'])
          ->first();

        if ($existingUser) {
          $skipped++;
          $errors[] = [
            'row' => $rowNumber,
            'message' => 'User already exists: ' . ($existingUser->user_login === $data['user_login'] ? 'Username' : 'Email') . ' already in use',
            'data' => $data
          ];
          return;
        }

        $salt = PasswordHelper::generateSalt();
        $plainPassword = !empty($data['user_pass'])
          ? (string) $data['user_pass']
          : PasswordHelper::generateTemporaryPassword();
        $hashedPassword = PasswordHelper::generatePassword($salt, $plainPassword);
        $activation_key = PasswordHelper::generateSalt();

        $userData = [
          'user_login' => $data['user_login'],
          'user_email' => $data['user_email'],
          'user_salt' => $salt,
          'user_pass' => $hashedPassword,
          'user_status' => $parseStatus($data['user_status'] ?? null),
          'user_activation_key' => $activation_key,
        ];

        $meta_details = [];
        foreach ([
          'first_name',
          'last_name',
          'employee_id',
          'position',
          'division',
          'project_name',
          'start_date',
          'outsourcing_agency',
          'cost_center',
          'licenses',
          'alt_email',
        ] as $metaKey) {
          if (!empty($data[$metaKey])) {
            $meta_details[$metaKey] = $data[$metaKey];
          }
        }

        $roleName = trim((string) ($data['role_name'] ?? ''));
        if ($roleName !== '') {
          $role = Role::whereRaw('LOWER(name) = ?', [strtolower($roleName)])->first();
          if ($role) {
            $meta_details['user_role'] = json_encode(['id' => (int) $role->id, 'name' => $role->name]);
            $userData['role_id'] = (int) $role->id;
          } else {
            $errors[] = [
              'row' => $rowNumber,
              'message' => "Role '{$roleName}' not found. User was imported without role assignment.",
              'data' => $data
            ];
          }
        } elseif (!empty($data['role_id'])) {
          $meta_details['user_role'] = json_encode(['id' => (int) $data['role_id']]);
          $userData['role_id'] = (int) $data['role_id'];
        }

        $originalPassword = request('user_pass');
        request()->merge(['user_pass' => $plainPassword]);

        try {
          $user = $this->service->storeWithMeta($userData, $meta_details);
          if ($user) {
            $imported++;
          } else {
            $skipped++;
            $errors[] = [
              'row' => $rowNumber,
              'message' => 'Failed to create user',
              'data' => $data
            ];
          }
        } finally {
          if ($originalPassword !== null) {
            request()->merge(['user_pass' => $originalPassword]);
          } else {
            request()->offsetUnset('user_pass');
          }
        }
      };

      if (in_array($extension, ['xlsx', 'xls'], true)) {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $worksheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
          $totalProcessed++;
          $startDateCell = $worksheet->getCellByColumnAndRow(7, $rowNumber);
          $rawStartDate = $startDateCell->getValue();

          $rowData = [
            'last_name' => trim((string) $worksheet->getCellByColumnAndRow(2, $rowNumber)->getFormattedValue()),
            'first_name' => trim((string) $worksheet->getCellByColumnAndRow(3, $rowNumber)->getFormattedValue()),
            'position' => trim((string) $worksheet->getCellByColumnAndRow(4, $rowNumber)->getFormattedValue()),
            'division' => trim((string) $worksheet->getCellByColumnAndRow(5, $rowNumber)->getFormattedValue()),
            'project_name' => trim((string) $worksheet->getCellByColumnAndRow(6, $rowNumber)->getFormattedValue()),
            'start_date' => is_numeric($rawStartDate) ? ExcelDate::excelToDateTimeObject($rawStartDate)->format('Y-m-d') : trim((string) $startDateCell->getFormattedValue()),
            'user_status' => trim((string) $worksheet->getCellByColumnAndRow(8, $rowNumber)->getFormattedValue()),
            'outsourcing_agency' => trim((string) $worksheet->getCellByColumnAndRow(9, $rowNumber)->getFormattedValue()),
            'cost_center' => trim((string) $worksheet->getCellByColumnAndRow(10, $rowNumber)->getFormattedValue()),
            'user_email' => trim((string) $worksheet->getCellByColumnAndRow(11, $rowNumber)->getFormattedValue()),
            'licenses' => trim((string) $worksheet->getCellByColumnAndRow(12, $rowNumber)->getFormattedValue()),
            'alt_email' => trim((string) $worksheet->getCellByColumnAndRow(13, $rowNumber)->getFormattedValue()),
            'role_name' => trim((string) $worksheet->getCellByColumnAndRow(14, $rowNumber)->getFormattedValue()),
          ];

          if (empty(array_filter($rowData))) {
            continue;
          }

          $createUserFromData($rowData, $rowNumber);
        }
      } else {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
          return response()->json([
            'message' => 'Failed to read CSV file',
            'errors' => ['file' => ['Could not open the CSV file.']]
          ], 422);
        }

        $headers = fgetcsv($handle);
        if ($headers === false || empty($headers)) {
          fclose($handle);
          return response()->json([
            'message' => 'Invalid CSV format',
            'errors' => ['file' => ['The CSV file is empty or has invalid format.']]
          ], 422);
        }

        $headers = array_map(function ($header) {
          return trim(strtolower((string) $header));
        }, $headers);

        $columnMap = [
          'username' => 'user_login',
          'email' => 'user_email',
          'password' => 'user_pass',
          'first name' => 'first_name',
          'last name' => 'last_name',
          'employee id' => 'employee_id',
          'position' => 'position',
          'status' => 'user_status',
          'role' => 'role_name',
          'role name' => 'role_name',
          'division' => 'division',
          'project name' => 'project_name',
          'start date' => 'start_date',
          'outsourcing agency' => 'outsourcing_agency',
          'cost center' => 'cost_center',
          'licenses' => 'licenses',
          'alt email' => 'alt_email',
          'two email for midc/pitc' => 'alt_email',
          'role id' => 'role_id',
        ];

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
          $rowNumber++;
          $totalProcessed++;

          if (empty(array_filter($row))) {
            continue;
          }

          $data = [];
          $skipRow = false;
          foreach ($headers as $index => $header) {
            $value = isset($row[$index]) ? trim((string) $row[$index]) : '';
            if (strpos($value, '#') === 0) {
              $skipRow = true;
              break;
            }
            if (isset($columnMap[$header])) {
              $data[$columnMap[$header]] = $value;
            }
          }

          if ($skipRow) {
            continue;
          }

          $createUserFromData($data, $rowNumber);
        }
        fclose($handle);
      }

      return response()->json([
        'data' => [
          'imported' => $imported,
          'skipped' => $skipped,
          'errors' => $errors,
          'total_processed' => $totalProcessed
        ],
        'message' => "Import completed. {$imported} user(s) imported, {$skipped} skipped."
      ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors()
      ], 422);
    } catch (\Exception $e) {
      \Log::error('[UserController] Import error:', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      
      return response()->json([
        'message' => 'Import failed: ' . $e->getMessage(),
        'errors' => []
      ], 500);
    }
  }
}
