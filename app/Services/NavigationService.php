<?php

namespace App\Services;

use App\Models\Navigation;
use App\Http\Resources\NavigationResource;

class NavigationService extends BaseService
{
  public function __construct()
  {
    // Pass the UserResource class to the parent constructor
    parent::__construct(new NavigationResource(new Navigation), new Navigation());
  }

  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allNavigations = $this->getTotalCount();
      $trashedNavigations = $this->getTrashedCount();

      $query = Navigation::query();
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where(function($q) {
          $q->where('name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('slug', 'LIKE', '%' . request('search') . '%');
        });
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
      } else {
        $query->orderBy('id', 'desc');
      }

      return NavigationResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allNavigations, 'trashed' => $trashedNavigations]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch navigations: ' . $e->getMessage());
    }
  }

  /**
  * Parent navigations.
  */
  public function navigations() 
  {
    return NavigationResource::collection(Navigation::query()->where('active', 1)->whereNull('parent_id')->orderBy('id', 'asc')->get());
  }

  /**
  * sub navigations.
  */
  public function subNavigations($id) 
  {
    return NavigationResource::collection(Navigation::query()->where('active', 1)->where('parent_id', $id)->orderBy('id', 'asc')->get());
  }

  public function getRoutes() 
  {
    $navigations = Navigation::loadTree(true); // true = only active items
    return NavigationResource::collection($navigations);
  }
}