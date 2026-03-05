<?php

namespace App\Http\Controllers\Api;

use App\Services\ContentReportsService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Content Reports",
 *     description="API endpoints for content reports"
 * )
 */
class ContentReportsController extends BaseController
{
	protected $contentReportsService;

	public function __construct(
		ContentReportsService $contentReportsService,
		MessageService $messageService
	) {
		parent::__construct($contentReportsService, $messageService);
		$this->contentReportsService = $contentReportsService;
	}

	/**
	 * Get content performance report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/content-reports/performance",
	 *     summary="Get content performance report",
	 *     tags={"Content Reports"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Parameter(
	 *         name="start_date",
	 *         in="query",
	 *         @OA\Schema(type="string", format="date")
	 *     ),
	 *     @OA\Parameter(
	 *         name="end_date",
	 *         in="query",
	 *         @OA\Schema(type="string", format="date")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Content performance report retrieved successfully"
	 *     )
	 * )
	 */
	public function getPerformanceReport(Request $request): JsonResponse
	{
		try {
			$filters = [
				'start_date' => $request->query('start_date'),
				'end_date' => $request->query('end_date'),
			];

			$data = $this->contentReportsService->getContentPerformanceReport($filters);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get category analysis report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/content-reports/categories",
	 *     summary="Get category analysis report",
	 *     tags={"Content Reports"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Category analysis report retrieved successfully"
	 *     )
	 * )
	 */
	public function getCategoryAnalysis(): JsonResponse
	{
		try {
			$data = $this->contentReportsService->getCategoryAnalysisReport();

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get tag analysis report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/content-reports/tags",
	 *     summary="Get tag analysis report",
	 *     tags={"Content Reports"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Tag analysis report retrieved successfully"
	 *     )
	 * )
	 */
	public function getTagAnalysis(): JsonResponse
	{
		try {
			$data = $this->contentReportsService->getTagAnalysisReport();

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get media library report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/content-reports/media",
	 *     summary="Get media library report",
	 *     tags={"Content Reports"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Parameter(
	 *         name="start_date",
	 *         in="query",
	 *         @OA\Schema(type="string", format="date")
	 *     ),
	 *     @OA\Parameter(
	 *         name="end_date",
	 *         in="query",
	 *         @OA\Schema(type="string", format="date")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Media library report retrieved successfully"
	 *     )
	 * )
	 */
	public function getMediaReport(Request $request): JsonResponse
	{
		try {
			$filters = [
				'start_date' => $request->query('start_date'),
				'end_date' => $request->query('end_date'),
			];

			$data = $this->contentReportsService->getMediaLibraryReport($filters);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}
}

