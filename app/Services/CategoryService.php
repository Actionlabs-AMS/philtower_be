<?php

namespace App\Services;

use App\Models\Category;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryCollectionResource;

class CategoryService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new CategoryResource(new Category), new Category());
  }
  
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allCategories = $this->getTotalCount();
      $trashedCategories = $this->getTrashedCount();

      $query = Category::query();
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where(function($q) {
          $q->where('name', 'LIKE', '%' . request('search') . '%')
            ->orWhere('slug', 'LIKE', '%' . request('search') . '%')
            ->orWhere('descriptions', 'LIKE', '%' . request('search') . '%');
        });
      }

      // Apply ordering
      if (request('order')) {
        $query->orderBy(request('order'), request('sort'));
      } else {
        $query->orderBy('id', 'desc');
      }

      return CategoryResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allCategories, 'trashed' => $trashedCategories]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch categories: ' . $e->getMessage());
    }
  }

  /**
  * Parent categories.
  */
  public function categories() 
  {
    return CategoryCollectionResource::collection(Category::query()->where('active', 1)->whereNull('parent_id')->orderBy('id', 'asc')->get());
  }

  /**
  * sub categories.
  */
  public function subCategories($id) 
  {
    return CategoryCollectionResource::collection(Category::query()->where('active', 1)->where('parent_id', $id)->orderBy('id', 'asc')->get());
  }
}