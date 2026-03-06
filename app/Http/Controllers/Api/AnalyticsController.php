<?php

namespace App\Http\Controllers\Api;

use App\Services\AnalyticsService;
use App\Services\MessageService;
use App\Services\Support\TicketAnalyticsService;
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

	/**
	 * Get ticket-based analytics overview (filters: service type, date range, statistics type).
	 *
	 * @OA\Get(
	 *     path="/api/analytics/tickets-overview",
	 *     summary="Get tickets analytics overview",
	 *     tags={"Analytics"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Parameter(name="service_type_id", in="query", @OA\Schema(type="integer")),
	 *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
	 *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
	 *     @OA\Parameter(name="statistics_type", in="query", @OA\Schema(type="string", enum={"tickets", "agents"})),
	 *     @OA\Response(response=200, description="Tickets overview retrieved successfully")
	 * )
	 */
	public function getTicketsOverview(Request $request, TicketAnalyticsService $ticketAnalytics): JsonResponse
	{
		try {
			$serviceTypeId = $request->query('service_type_id') ? (int) $request->query('service_type_id') : null;
			$dateFrom = $request->query('date_from') ?: null;
			$dateTo = $request->query('date_to') ?: null;
			$statisticsType = $request->query('statistics_type', 'tickets');
			if (!in_array($statisticsType, ['tickets', 'agents'], true)) {
				$statisticsType = 'tickets';
			}

			$data = $ticketAnalytics->getTicketsOverview($serviceTypeId, $dateFrom, $dateTo, $statisticsType);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}
}

