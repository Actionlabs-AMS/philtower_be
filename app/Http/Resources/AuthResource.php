<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		// Get user role with permissions
		// In sima-api style: user_role is appended to User model, and permissions is appended to Role model
		// When we access $this->user_role, it returns the Role model, which has permissions appended
		$userRole = $this->user_role;
		
		// Build user_role object with permissions (same structure as sima-api)
		// The Role model's permissions attribute will be automatically included when we serialize
		$userRoleData = null;
		if ($userRole) {
			// Access permissions - this will use the appended 'permissions' attribute from Role model
			// which calls getPermissionsFormatted()
			// Force fresh calculation by calling getPermissionsFormatted() directly
			$permissions = $userRole->getPermissionsFormatted();
			
			// Get specific navigation 3 permissions for testing
			$nav3ParentId = 2; // Navigation 3 (All Users) has parent_id = 2 (User Management)
			$nav3Perms = $permissions[$nav3ParentId][3] ?? null;
			
			// Log permissions for debugging
			\Log::info('AuthResource - User ID: ' . $this->id, [
				'role_id' => $userRole->id,
				'role_name' => $userRole->name,
				'is_super_admin' => $userRole->is_super_admin ?? false,
				'permissions_type' => gettype($permissions),
				'permissions_count' => is_array($permissions) ? count($permissions) : 0,
				'permissions_keys' => is_array($permissions) ? array_keys(array_slice($permissions, 0, 5, true)) : null,
				'nav3_permissions' => $nav3Perms, // Specific logging for navigation 3
				'sample_structure' => is_array($permissions) ? array_map(function($parentKey) use ($permissions) {
					return [
						'parent' => $parentKey,
						'children' => array_keys(array_slice($permissions[$parentKey], 0, 3, true)),
					];
				}, array_slice(array_keys($permissions), 0, 3)) : null,
			]);
			
			// Build user_role as JSON string (sima-admin expects it as a string)
			// Include the full role object with permissions
			$userRoleData = [
				'id' => $userRole->id,
				'name' => $userRole->name,
				'is_super_admin' => $userRole->is_super_admin ?? false,
				'permissions' => $permissions,
			];
		}
		
		return [
      'id' => $this->id,
      'user_login' => $this->user_login,
      'first_name' => (isset($this->user_details['first_name'])) ? $this->user_details['first_name'] : '',
      'last_name' => (isset($this->user_details['last_name'])) ? $this->user_details['last_name'] : '',
      'attachment_file' => (isset($this->user_details['attachment_file'])) ? $this->user_details['attachment_file'] : '',
      'user_routes' => $userRole ? $userRole->getUserRoutes() : [],
      'user_role' => $userRoleData ? json_encode($userRoleData) : null, // JSON string as sima-admin expects
      'theme' => (isset($this->user_details['theme'])) ? $this->user_details['theme'] : '',
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
	}
}
