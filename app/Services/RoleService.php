<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;

class RoleService extends BaseService
{
  public function __construct()
  {
    // Pass the UserResource class to the parent constructor
    parent::__construct(new RoleResource(new Role), new Role());
  }

  /**
   * Override getTotalCount to exclude Developer Account
   */
  public function getTotalCount()
  {
    return Role::where('name', '!=', 'Developer Account')->count();
  }

  /**
   * Override getTrashedCount to exclude Developer Account
   */
  public function getTrashedCount()
  {
    return Role::onlyTrashed()->where('name', '!=', 'Developer Account')->count();
  }

  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allRoles = $this->getTotalCount();
      $trashedRoles = $this->getTrashedCount();

      $query = Role::query();
      
      // Exclude Developer Account role
      $query->where('name', '!=', 'Developer Account');
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where('name', 'LIKE', '%' . request('search') . '%');
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
      } else {
        $query->orderBy('id', 'desc');
      }

      return RoleResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allRoles, 'trashed' => $trashedRoles]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch roles: ' . $e->getMessage());
    }
  }

  public function getRoles() 
  {
    return Role::query()
      ->select('id', 'name', 'name as  label')
      ->where('active', 1)
      ->where('name', '!=', 'Developer Account')
      ->orderBy('id', 'asc')
      ->get()
      ->makeHidden(['permissions']);
  }
}