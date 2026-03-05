<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use App\Services\MessageService;
use App\Services\WidgetDataService;
use App\Models\DashboardWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API endpoints for dashboard statistics and widgets"
 * )
 */
class DashboardController extends BaseController
{
	protected $widgetDataService;

	public function __construct(
		DashboardService $dashboardService,
		MessageService $messageService,
		WidgetDataService $widgetDataService
	) {
		// Call the parent constructor to initialize services
		parent::__construct($dashboardService, $messageService);
		$this->widgetDataService = $widgetDataService;
	}

	/**
	 * Get dashboard statistics
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/stats",
	 *     summary="Get dashboard statistics",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Dashboard statistics retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="total_users", type="integer", example=150),
	 *                 @OA\Property(property="total_media", type="integer", example=234),
	 *                 @OA\Property(property="total_categories", type="integer", example=45),
	 *                 @OA\Property(property="total_tags", type="integer", example=128)
	 *             )
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
	public function getStats(): JsonResponse
	{
		try {
			$stats = $this->service->getStats();

			return response()->json([
				'success' => true,
				'data' => $stats,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get enhanced dashboard statistics
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/enhanced-stats",
	 *     summary="Get enhanced dashboard statistics",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Enhanced dashboard statistics retrieved successfully"
	 *     )
	 * )
	 */
	public function getEnhancedStats(): JsonResponse
	{
		try {
			$stats = $this->service->getEnhancedStats();

			return response()->json([
				'success' => true,
				'data' => $stats,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get all dashboard widgets for current user
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/widgets",
	 *     summary="Get dashboard widgets",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Dashboard widgets retrieved successfully"
	 *     )
	 * )
	 */
	public function getWidgets(Request $request): JsonResponse
	{
		try {
			$userId = $request->user()?->id;
			
			$widgets = DashboardWidget::forUser($userId)
				->active()
				->ordered()
				->get();

			return response()->json([
				'success' => true,
				'data' => $widgets,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get widget data
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/widgets/{id}/data",
	 *     summary="Get widget data",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         @OA\Schema(type="integer")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Widget data retrieved successfully"
	 *     )
	 * )
	 */
	public function getWidgetData(int $id): JsonResponse
	{
		try {
			$widget = DashboardWidget::findOrFail($id);
			$data = $this->widgetDataService->getWidgetData($widget);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to retrieve widget data: ' . $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get user registration trend
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/user-registration-trend",
	 *     summary="Get user registration trend",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Parameter(
	 *         name="period",
	 *         in="query",
	 *         @OA\Schema(type="string", enum={"daily", "weekly", "monthly"})
	 *     ),
	 *     @OA\Parameter(
	 *         name="days",
	 *         in="query",
	 *         @OA\Schema(type="integer", default=30)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="User registration trend retrieved successfully"
	 *     )
	 * )
	 */
	public function getUserRegistrationTrend(Request $request): JsonResponse
	{
		try {
			$period = $request->query('period', 'daily');
			$days = (int) $request->query('days', 30);
			
			$trend = $this->service->getUserRegistrationTrend($period, $days);

			return response()->json([
				'success' => true,
				'data' => $trend,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get pages by status
	 * 
	 * @OA\Get(
	 *     path="/api/dashboard/pages-by-status",
	 *     summary="Get pages by status",
	 *     tags={"Dashboard"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Pages by status retrieved successfully"
	 *     )
	 * )
	 */
	public function getPagesByStatus(): JsonResponse
	{
		try {
			$data = $this->service->getPagesByStatus();

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}
}