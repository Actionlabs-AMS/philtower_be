<?php

namespace App\Services;

use App\Models\Tag;
use App\Http\Resources\TagResource;

class TagService extends BaseService
{
  public function __construct()
  {
      // Pass the UserResource class to the parent constructor
      parent::__construct(new TagResource(new Tag), new Tag());
  }

  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allTags = $this->getTotalCount();
      $trashedTags = $this->getTrashedCount();

      $query = Tag::query();
      
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

      return TagResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allTags, 'trashed' => $trashedTags]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch tags: ' . $e->getMessage());
    }
  }

  /**
  * Tags.
  */
  public function tags() 
  {
    return TagResource::collection(Tag::query()->orderBy('id', 'asc')->get());
  }
}