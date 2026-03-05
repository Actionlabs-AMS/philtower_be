<?php

namespace App\Http\Controllers\Api;

use App\Services\UserReportsService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="User Reports",
 *     description="API endpoints for user reports"
 * )
 */
class UserReportsController extends BaseController
{
	protected $userReportsService;

	public function __construct(
		UserReportsService $userReportsService,
		MessageService $messageService
	) {
		parent::__construct($userReportsService, $messageService);
		$this->userReportsService = $userReportsService;
	}

	/**
	 * Get user registration report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/user-reports/registration",
	 *     summary="Get user registration report",
	 *     tags={"User Reports"},
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
	 *     @OA\Parameter(
	 *         name="period",
	 *         in="query",
	 *         @OA\Schema(type="string", enum={"daily", "weekly", "monthly"})
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="User registration report retrieved successfully"
	 *     )
	 * )
	 */
	public function getRegistrationReport(Request $request): JsonResponse
	{
		try {
			$filters = [
				'start_date' => $request->query('start_date'),
				'end_date' => $request->query('end_date'),
				'period' => $request->query('period', 'daily'),
			];

			$data = $this->userReportsService->getUserRegistrationReport($filters);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get user engagement report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/user-reports/engagement",
	 *     summary="Get user engagement report",
	 *     tags={"User Reports"},
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
	 *         description="User engagement report retrieved successfully"
	 *     )
	 * )
	 */
	public function getEngagementReport(Request $request): JsonResponse
	{
		try {
			$filters = [
				'start_date' => $request->query('start_date'),
				'end_date' => $request->query('end_date'),
			];

			$data = $this->userReportsService->getUserEngagementReport($filters);

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}

	/**
	 * Get user demographics report
	 * 
	 * @OA\Get(
	 *     path="/api/analytics/user-reports/demographics",
	 *     summary="Get user demographics report",
	 *     tags={"User Reports"},
	 *     security={{"sanctum": {}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="User demographics report retrieved successfully"
	 *     )
	 * )
	 */
	public function getDemographicsReport(): JsonResponse
	{
		try {
			$data = $this->userReportsService->getUserDemographicsReport();

			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);
		} catch (\Exception $e) {
			return $this->messageService->responseError();
		}
	}
}

