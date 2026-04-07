<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserActivityService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="User Activity",
 *     description="API endpoints for user activity tracking"
 * )
 */
class UserActivityController extends Controller
{
    protected $userActivityService;

    public function __construct(UserActivityService $userActivityService)
    {
        $this->userActivityService = $userActivityService;
    }

    /**
     * Get all activities for a specific user
     * 
     * @OA\Get(
     *     path="/api/user-management/user-activity/{userId}",
     *     summary="Get user activities",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="module",
     *         in="query",
     *         description="Filter by module",
     *         @OA\Schema(type="string", example="AUTHENTICATION")
     *     ),
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Filter by action",
     *         @OA\Schema(type="string", example="LOGIN_SUCCESS")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit results",
     *         @OA\Schema(type="integer", example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activities retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUserActivities(Request $request, int $userId): JsonResponse
    {
        try {
            // Verify user exists
            $user = User::findOrFail($userId);

            $filters = [
                'module' => $request->query('module'),
                'action' => $request->query('action'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'limit' => $request->query('limit', 100),
            ];

            $activities = $this->userActivityService->getUserActivities($userId, $filters);

            return response()->json([
                'data' => $activities,
                'count' => count($activities),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user activities',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get login history for a user
     * 
     * @OA\Get(
     *     path="/api/user-management/user-activity/{userId}/login-history",
     *     summary="Get user login history",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit results",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login history retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getLoginHistory(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'limit' => $request->query('limit', 50),
            ];

            $loginHistory = $this->userActivityService->getUserLoginHistory($userId, $filters);

            return response()->json([
                'data' => $loginHistory,
                'count' => count($loginHistory),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve login history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export login history for all users (audit log + API tokens).
     */
    public function exportAllLoginHistory(Request $request): JsonResponse
    {
        try {
            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'limit' => $request->query('limit', 10000),
            ];

            $loginHistory = $this->userActivityService->getAllUsersLoginHistory($filters);

            return response()->json([
                'data' => $loginHistory,
                'count' => count($loginHistory),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export login history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active sessions for a user
     * 
     * @OA\Get(
     *     path="/api/user-management/user-activity/{userId}/sessions",
     *     summary="Get user active sessions",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Active sessions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getActiveSessions(int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $sessions = $this->userActivityService->getActiveSessions($userId);

            return response()->json([
                'data' => $sessions,
                'count' => count($sessions),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve active sessions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke a user session
     * 
     * @OA\Post(
     *     path="/api/user-management/user-activity/{userId}/sessions/{tokenId}/revoke",
     *     summary="Revoke a user session",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="tokenId",
     *         in="path",
     *         required=true,
     *         description="Token ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session revoked successfully"
     *     )
     * )
     */
    public function revokeSession(int $userId, int $tokenId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $token = $user->tokens()->find($tokenId);
            if (!$token) {
                return response()->json([
                    'message' => 'Token not found',
                ], 404);
            }

            $token->delete();

            return response()->json([
                'message' => 'Session revoked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to revoke session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity timeline for a user
     * 
     * @OA\Get(
     *     path="/api/user-management/user-activity/{userId}/timeline",
     *     summary="Get user activity timeline",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activity timeline retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getUserTimeline(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'limit' => $request->query('limit', 200),
            ];

            $timeline = $this->userActivityService->getUserTimeline($userId, $filters);

            return response()->json([
                'data' => $timeline,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve activity timeline',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get activity statistics for a user
     * 
     * @OA\Get(
     *     path="/api/user-management/user-activity/{userId}/statistics",
     *     summary="Get user activity statistics",
     *     tags={"User Activity"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activity statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getActivityStatistics(Request $request, int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $filters = [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ];

            $statistics = $this->userActivityService->getActivityStatistics($userId, $filters);

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve activity statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

