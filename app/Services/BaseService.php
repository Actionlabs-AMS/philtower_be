<?php

namespace App\Services;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Builder;

class BaseService
{
  protected $resource;
  protected $model;

  // Constructor accepts the Resource class dynamically
  public function __construct(JsonResource $resource, Model $model)
  {
    $this->resource = $resource;
    $this->model = $model;
  }

  // Optimized count methods
  public function getTotalCount()  
  {
    return $this->model::count();
  }

  // Optimized count methods
  public function getTrashedCount() 
  {
    return $this->model::onlyTrashed()->count();
  }

  // Refactored find method to avoid repetition
  private function findModelById($id)
  {
    return $this->model::findOrFail($id);
  }

  // Store a newly created resource in storage.
  public function store(array $data) 
  {
    $model = $this->model::create($data);
    return $this->resource::make($model);
  }

  // Get Details for editing the specified resource.
  public function show(int $id) 
  {
    $model = $this->findModelById($id);
    return $model;
    // NOT USING RESOURCE BECAUSE IT'S RE-CREATING ARRAY
    // return $this->resource::make($model);
  }

  // Update the specified resource in storage.
  public function update(array $data, int $id)
  {
    $model = $this->findModelById($id);
    $model->update($data);
    return $this->resource::make($model);
  }

  // Remove the specified resource from storage.
  public function destroy($id)
  {
    $model = $this->findModelById($id);
    return $model->delete();
  }

  // Restore resource with soft-deleted from storage.
  public function restore($id)
  {
    $model = $this->model::withTrashed()->findOrFail($id);
    $model->restore();
    return $this->resource::make($model);
  }

  // Permanently delete a soft-deleted from storage.
  public function forceDelete($id)
  {
    $model = $this->model::withTrashed()->findOrFail($id);
    $model->forceDelete();
    return $model;
  }

  // Bulk delete a soft-deleted from storage.
  public function bulkDelete($ids) 
  {
    return $this->model::whereIn('id', $ids)->delete();
  }

  // Bulk restore a soft-deleted from storage.
  public function bulkRestore($ids) 
  {
    return $this->model::withTrashed()->whereIn('id', $ids)->restore();
  }

  // Bulk force deleted from storage.
  public function bulkForceDelete($ids) 
  {
    return $this->model::withTrashed()->whereIn('id', $ids)->forceDelete();
  }

  //Handle lazy loading of large collections
  public function getLazyCollection($query)
  {
    return LazyCollection::make(function () use ($query) {
      foreach ($query->cursor() as $item) {
        yield $this->resource::make($item);
      }
    });
  }

  /**
   * Retrieve all resources lazily.
   * 
   * @param  Builder  $query
   * @param  int  $perPage
   * @return LazyCollection
   */
  public function getAllLazily(Builder $query, $perPage = 10)
  {
    // Apply pagination logic while using LazyCollection for large datasets
    return $this->getLazyCollection($query->paginate($perPage));
  }

  // Lazy load all records, no pagination
  public function collections() 
  {
    return $this->getLazyCollection($this->model::query()->orderBy('id', 'asc'));
  }

  // Lazy load records with pagination
  public function paginatedCollections($perPage = 10) 
  {
    return $this->getAllLazily($this->model::query()->orderBy('id', 'asc'), $perPage);
  }
}