<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Requests\PageRequest;
use App\Services\PageService;
use App\Services\MessageService;

/**
 * @OA\Tag(
 *     name="Page Management",
 *     description="API endpoints for page management"
 * )
 */
class PageController extends BaseController
{
  public function __construct(PageService $pageService, MessageService $messageService)
  {
    // Call the parent constructor to initialize services
    parent::__construct($pageService, $messageService);
  }

  /**
   * Display a listing of pages.
   * 
   * @OA\Get(
   *     path="/api/content-management/pages",
   *     summary="Get list of pages",
   *     tags={"Page Management"},
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
   *         description="List of pages retrieved successfully",
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
   * Display the specified page.
   * 
   * @OA\Get(
   *     path="/api/content-management/pages/{id}",
   *     summary="Get a specific page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Page ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Page retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Page not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page not found.")
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
   * Remove the specified page from storage (soft delete).
   * 
   * @OA\Delete(
   *     path="/api/content-management/pages/{id}",
   *     summary="Delete a page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Page ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Page moved to trash successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been moved to trash.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Page not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page not found.")
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
   * Bulk delete multiple pages.
   * 
   * @OA\Post(
   *     path="/api/content-management/pages/bulk/delete",
   *     summary="Bulk delete multiple pages",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of page IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Pages deleted successfully",
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
   * Get trashed pages.
   * 
   * @OA\Get(
   *     path="/api/content-management/archived/pages",
   *     summary="Get trashed pages",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Response(
   *         response=200,
   *         description="Trashed pages retrieved successfully",
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
   * Restore a trashed page.
   * 
   * @OA\Patch(
   *     path="/api/content-management/archived/pages/restore/{id}",
   *     summary="Restore a trashed page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Page ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Page restored successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been restored."),
   *             @OA\Property(property="resource", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Page not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page not found.")
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
   * Bulk restore multiple trashed pages.
   * 
   * @OA\Post(
   *     path="/api/content-management/pages/bulk/restore",
   *     summary="Bulk restore multiple trashed pages",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of page IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Pages restored successfully",
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
   * Permanently delete a page.
   * 
   * @OA\Delete(
   *     path="/api/content-management/archived/pages/{id}",
   *     summary="Permanently delete a page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Page ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Page permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Resource has been permanently deleted.")
   *         )
   *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Page not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Page not found.")
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
   * Bulk permanently delete multiple pages.
   * 
   * @OA\Post(
   *     path="/api/content-management/pages/bulk/force-delete",
   *     summary="Bulk permanently delete multiple pages",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"ids"},
   *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3}, description="Array of page IDs")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Pages permanently deleted successfully",
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
   *     path="/api/content-management/pages",
   *     summary="Create a new page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"title", "slug"},
   *             @OA\Property(property="title", type="string", example="About Us", description="Page title"),
   *             @OA\Property(property="slug", type="string", example="about-us", description="Page slug"),
   *             @OA\Property(property="content", type="string", example="<p>Page content here</p>", description="Legacy content field (kept for backward compatibility)"),
   *             @OA\Property(property="layout_structure", type="object", description="Page builder structure (JSON) with nested blocks including columns support"),
   *             @OA\Property(property="layout", type="string", example="default", description="Page layout template (always 'default' for page builder)"),
   *             @OA\Property(property="status", type="string", example="draft", description="Page status: draft, published, scheduled"),
   *             @OA\Property(property="featured_image", type="string", example="", description="Legacy featured image (images now added via page builder blocks)"),
   *             @OA\Property(property="meta_title", type="string", example="", description="SEO meta title"),
   *             @OA\Property(property="meta_description", type="string", example="", description="SEO meta description"),
   *             @OA\Property(property="published_at", type="string", example="2024-01-01T00:00:00Z", description="Publish date (required for scheduled status)"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Whether the page is active")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Page created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page has been created successfully."),
   *             @OA\Property(property="page", type="object")
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
  public function store(PageRequest $request)
  {
    try {
      $data = $request->all();
      // Set author_id to current user if not provided
      if (!isset($data['author_id'])) {
        $data['author_id'] = auth()->id();
      }
      // Ensure layout is set to 'default' for page builder (layout_structure takes precedence)
      if (!isset($data['layout']) || empty($data['layout'])) {
        $data['layout'] = 'default';
      }
      // Set featured_image to null if not provided (images now added via page builder blocks)
      if (!isset($data['featured_image'])) {
        $data['featured_image'] = null;
      }
      // Set published_at if status is published and published_at is not set
      if (($data['status'] ?? 'draft') === 'published' && !isset($data['published_at'])) {
        $data['published_at'] = now();
      }
      $page = $this->service->store($data);
      return response($page, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }

  /**
   * Update the specified resource in storage.
   * 
   * @OA\Put(
   *     path="/api/content-management/pages/{id}",
   *     summary="Update a page",
   *     tags={"Page Management"},
   *     security={{"sanctum": {}}},
   *     @OA\Parameter(
   *         name="id",
   *         in="path",
   *         required=true,
   *         description="Page ID",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(property="title", type="string", example="About Us", description="Page title"),
   *             @OA\Property(property="slug", type="string", example="about-us", description="Page slug"),
   *             @OA\Property(property="content", type="string", example="<p>Page content here</p>", description="Legacy content field (kept for backward compatibility)"),
   *             @OA\Property(property="layout_structure", type="object", description="Page builder structure (JSON) with nested blocks including columns support"),
   *             @OA\Property(property="layout", type="string", example="default", description="Page layout template (always 'default' for page builder)"),
   *             @OA\Property(property="status", type="string", example="published", description="Page status: draft, published, scheduled"),
   *             @OA\Property(property="featured_image", type="string", example="", description="Legacy featured image (images now added via page builder blocks)"),
   *             @OA\Property(property="meta_title", type="string", example="", description="SEO meta title"),
   *             @OA\Property(property="meta_description", type="string", example="", description="SEO meta description"),
   *             @OA\Property(property="published_at", type="string", example="2024-01-01T00:00:00Z", description="Publish date (required for scheduled status)"),
   *             @OA\Property(property="active", type="boolean", example=true, description="Whether the page is active")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Page updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page has been updated successfully."),
   *             @OA\Property(property="page", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Page not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Page not found.")
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
  public function update(PageRequest $request, int $id)
  {
    try {
      $data = $request->all();
      // Ensure layout is set to 'default' for page builder (layout_structure takes precedence)
      if (!isset($data['layout']) || empty($data['layout'])) {
        $data['layout'] = 'default';
      }
      // Set featured_image to null if not provided (images now added via page builder blocks)
      if (!isset($data['featured_image'])) {
        $data['featured_image'] = null;
      }
      // Set published_at if status is published and published_at is not set
      if (($data['status'] ?? 'draft') === 'published' && !isset($data['published_at'])) {
        $data['published_at'] = now();
      }
      $page = $this->service->update($data, $id);
      return response($page, 201);
    } catch (\Exception $e) {
      return $this->messageService->responseError();
    }
  }
}

