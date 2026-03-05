<?php

namespace App\Http\Controllers\Api;

use App\Services\AnalyticsService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Analytics",
 *     description="API endpoints for analytics overview"
 * )
 */
class AnalyticsController extends BaseController
{
	protected $analyticsService;

	public function __construct(
		AnalyticsService $analyticsService,
		MessageService $messageService
	) {
		parent::__construct($analyticsService, $messageService);
		$this->analyticsService = $analyticsService;
	}

	/**
	 * Get analytics overview
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/overview",
	 *     summary="Get analytics overview",
	 *     tags={"Analytics"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Analytics overview retrieved successfully"
	 *     )
	 * )
	 */
	public function getOverview(): JsonResponse
	{
		try {
			$data = $this->analyticsService->getAnalyticsOverview();

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}
}

