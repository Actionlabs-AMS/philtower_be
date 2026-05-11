<?php

namespace App\Services;

use App\Models\Department;
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new DepartmentResource(new Department), new Department());
  }

    /**
     * Retrieve departments with pagination + trash support
     */
    public function list($perPage = 10, $trash = false): AnonymousResourceCollection
    {
      try {
        $allDepartments = $this->getTotalCount();
        $trashedDepartments = $this->getTrashedCount();

        $query = Department::query();
        
        // Apply onlyTrashed() first if we're in trash view
        if ($trash) {
          $query = $query->onlyTrashed();
        }

        // Then apply search conditions
        if (request('search')) {
          $query->where(function($q) {
            $q->where('name', 'LIKE', '%' . request('search') . '%')
              ->orWhere('code', 'LIKE', '%' . request('search') . '%')
              ->orWhere('descriptions', 'LIKE', '%' . request('search') . '%');
          });
        }

        // Apply ordering
        if (request('order')) {
          $query->orderBy(request('order'), request('sort'));
        } else {
          $query->orderBy('id', 'desc');
        }

        return DepartmentResource::collection(
          $query->paginate($perPage)->withQueryString()
        )->additional(['meta' => ['all' => $allDepartments, 'trashed' => $trashedDepartments]]);
      } catch (\Exception $e) {
        throw new \Exception('Failed to fetch departments: ' . $e->getMessage());
      }
    }
}