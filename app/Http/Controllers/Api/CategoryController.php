<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Category Management",
 *     description="API endpoints for category management"
 * )
 */
class CategoryController extends BaseController
{
  public function __construct(CategoryService $categoryService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($categoryService, $messageService);
  }

  /**
   * Display a listing of categories.
   * 
   * @OA\Get(
   *     path="/api/content-management/categories",
   *     summary="Get list of categories",
   *     tags={"Category Management"},
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
   *         description="List of categories retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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
   * Display the specified category.
   * 
   * @OA\Get(
   *     path="/api/content-management/categories/{id}",
   *     summary="Get a specific category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Category retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
   * Remove the specified category from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/content-management/categories/{id}",
   *     summary="Delete a category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Category moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
   * Bulk delete multiple categories.
   * 
   * @OA\Post(
   *     path="/api/content-management/categories/bulk/delete",
   *     summary="Bulk delete multiple categories",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of category IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Categories deleted successfully",
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
   * Get trashed categories.
   * 
   * @OA\Get(
   *     path="/api/content-management/archived/categories",
   *     summary="Get trashed categories",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed categories retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
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
   * Restore a trashed category.
   * 
   * @OA\Patch(
   *     path="/api/content-management/archived/categories/restore/{id}",
   *     summary="Restore a trashed category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Category restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
   * Bulk restore multiple trashed categories.
   * 
   * @OA\Post(
   *     path="/api/content-management/categories/bulk/restore",
   *     summary="Bulk restore multiple trashed categories",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of category IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Categories restored successfully",
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
   * Permanently delete a category.
   * 
   * @OA\Delete(
   *     path="/api/content-management/archived/categories/{id}",
   *     summary="Permanently delete a category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Category permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
   * Bulk permanently delete multiple categories.
   * 
   * @OA\Post(
   *     path="/api/content-management/categories/bulk/force-delete",
   *     summary="Bulk permanently delete multiple categories",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of category IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Categories permanently deleted successfully",
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
   * Store a newly created resource in storage.
   * 
   * @OA\Post(
   *     path="/api/content-management/categories",
   *     summary="Create a new category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"name"},
   *             @OA\Property(property="name", type="string", example="Technology", description="Category name"),
   *             @OA\Property(property="description", type="string", example="Technology related content", description="Category description"),
   *             @OA\Property(property="parent_id", type="integer", example=null, description="Parent category ID (for subcategories)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Category created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category has been created successfully."),
   *             @OA\Property(property="category", type="object")
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
  public function store(CategoryRequest $request)
  {
    // try {
      $data = $request->all();
      $category = $this->service->store($data);
      return response($category, 201);
    // } catch (\Exception $e) {
    //   return $this->messageService->responseError();
    // }
  }

  /**
   * Update the specified resource in storage.
   * 
   * @OA\Put(
   *     path="/api/content-management/categories/{id}",
   *     summary="Update a category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="name", type="string", example="Technology", description="Category name"),
   *             @OA\Property(property="description", type="string", example="Technology related content", description="Category description"),
   *             @OA\Property(property="parent_id", type="integer", example=null, description="Parent category ID (for subcategories)")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Category updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category has been updated successfully."),
   *             @OA\Property(property="category", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
  public function update(CategoryRequest $request, int $id)
  {
    // try {
      $data = $request->all();
      $category = $this->service->update($data, $id);
      return response($category, 201);
    // } catch (\Exception $e) {
    //   return $this->messageService->responseError();
    // }
  }

  /**
   * Get all categories for dropdown/options.
   * 
   * @OA\Get(
   *     path="/api/options/categories",
   *     summary="Get all categories for dropdown",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Categories retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
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
  public function getParentCategories() 
  {
    try {
			return $this->service->categories();
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Get subcategories for a specific category.
   * 
   * @OA\Get(
   *     path="/api/options/categories/{id}",
   *     summary="Get subcategories for a specific category",
   *     tags={"Category Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Parent category ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Subcategories retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Category not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Category not found.")
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
  public function getSubCategories($id) 
  {
    try {
			return $this->service->subCategories($id);
		} catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}
