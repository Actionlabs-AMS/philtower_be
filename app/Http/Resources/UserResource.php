<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Get user role with permissions (same as AuthResource)
    $userRole = $this->user_role;
    
    // Build user_role object with permissions
    $userRoleData = null;
    $userRoutes = [];
    
    if ($userRole) {
      // Access permissions - this will use the appended 'permissions' attribute from Role model
      // which calls getPermissionsFormatted()
      // Force fresh calculation by calling getPermissionsFormatted() directly (same as AuthResource)
      $permissions = $userRole->getPermissionsFormatted();
      
      $userRoleData = [
        'id' => $userRole->id,
        'name' => $userRole->name,
        'is_super_admin' => $userRole->is_super_admin ?? false,
        'permissions' => $permissions,
      ];
      
      // Get user routes - ensure permissions are accessible on the role model
      // The getUserRoutes() method checks for $this->permissions, so we need to set it
      // Access the permissions attribute to ensure it's loaded, then call getUserRoutes
      $userRole->setAttribute('permissions', $permissions);
      $userRoutes = $userRole->getUserRoutes();
    }

    $approverId = $this->getMeta('approver_id');
    $approverName = null;
    if (!empty($approverId)) {
      $approver = User::find((int) $approverId);
      if ($approver) {
        $first = null;
        $last = null;
        try {
          $first = $approver->getMeta('first_name');
          $last = $approver->getMeta('last_name');
        } catch (\Throwable $e) {
          // Fallback to user_login when meta is unavailable.
        }
        $fullName = trim((string) ($first ?? '') . ' ' . (string) ($last ?? ''));
        $approverName = $fullName !== '' ? $fullName : ($approver->user_login ?? null);
      }
    }
    
    return [
      'id' => $this->id,
      'user_login' => $this->user_login,
      'user_email' => $this->user_email,
      'first_name' => $this->user_details['first_name'] ?? null,
      'last_name' => $this->user_details['last_name'] ?? null,
      'nickname' => $this->user_details['nickname'] ?? null,
      'employee_id' => $this->user_details['employee_id'] ?? null,
      'position' => $this->user_details['position'] ?? null,
      'mobile_number' => $this->user_details['mobile_number'] ?? null,
      'contact_number' => $this->user_details['contact_number'] ?? null,
      'biography' => $this->user_details['biography'] ?? null,
      'attachment_file' => $this->user_details['attachment_file'] ?? null,
      'attachment_metadata' => $this->user_details['attachment_metadata'] ?? null,
      'user_routes' => $userRoutes, // Add user_routes like AuthResource
      'user_role' => $userRoleData ? json_encode($userRoleData) : null, // JSON string as sima-admin expects
      'user_role_name' => ($userRole) ? $userRole->name : 'Unassigned',
      'role_name' => ($userRole) ? $userRole->name : 'Unassigned', // Added for frontend table
      'role_id' => $this->role_id, // Added for frontend
      'approver_id' => !empty($approverId) ? (int) $approverId : null,
      'approver_name' => $approverName,
      'theme' => $this->user_details['theme'] ?? null,
      'user_status' => $this->user_status, // Return numeric status for badge logic
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}
