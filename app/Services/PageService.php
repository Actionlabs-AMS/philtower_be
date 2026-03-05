<?php

namespace App\Services;

use App\Models\Page;
use App\Http\Resources\PageResource;

class PageService extends BaseService
{
  public function __construct()
  {
      // Pass the PageResource class to the parent constructor
      parent::__construct(new PageResource(new Page), new Page());
  }
  
  /**
  * Retrieve all resources with paginate.
  */
  public function list($perPage = 10, $trash = false)
  {
    try {
      $allPages = $this->getTotalCount();
      $trashedPages = $this->getTrashedCount();

      $query = Page::query()->with('author');
      
      // Apply onlyTrashed() first if we're in trash view
      if ($trash) {
        $query->onlyTrashed();
      }

      // Then apply search conditions
      if (request('search')) {
        $query->where(function($q) {
          $q->where('title', 'LIKE', '%' . request('search') . '%')
            ->orWhere('slug', 'LIKE', '%' . request('search') . '%')
            ->orWhere('content', 'LIKE', '%' . request('search') . '%')
            ->orWhere('meta_title', 'LIKE', '%' . request('search') . '%');
        });
      }

      // Apply status filter (handle both 'status' and 'status_label' parameters)
      $statusFilter = request('status') ?? request('status_label');
      if ($statusFilter) {
        $query->where('status', $statusFilter);
      }

      // Apply ordering
      if (request('order')) {
        $orderBy = request('order');
        // Map frontend field names to database column names
        $columnMap = [
          'status_label' => 'status', // Map status_label to status column
        ];
        $orderBy = $columnMap[$orderBy] ?? $orderBy;
        $query->orderBy($orderBy, request('sort') ?? 'asc');
      } else {
        $query->orderBy('id', 'desc');
      }

      return PageResource::collection(
        $query->paginate($perPage)->withQueryString()
      )->additional(['meta' => ['all' => $allPages, 'trashed' => $trashedPages]]);
    } catch (\Exception $e) {
      throw new \Exception('Failed to fetch pages: ' . $e->getMessage());
    }
  }
}

