<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;

class UserReportsService
{
	protected $userActivityService;

	public function __construct(UserActivityService $userActivityService = null)
	{
		$this->userActivityService = $userActivityService;
	}

	/**
	 * Get user registration report
	 * 
	 * @param array $filters
	 * @return array
	 */
	public function getUserRegistrationReport(array $filters = []): array
	{
		try {
			$startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
			$endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
			$period = $filters['period'] ?? 'daily';

			$users = User::whereBetween('created_at', [$startDate, $endDate])
				->orderBy('created_at', 'asc')
				->get(['created_at', 'user_status', 'role_id']);

			$trend = [];
			$format = match ($period) {
				'weekly' => 'Y-W',
				'monthly' => 'Y-m',
				default => 'Y-m-d',
			};

			foreach ($users as $user) {
				$key = $user->created_at->format($format);
				$trend[$key] = ($trend[$key] ?? 0) + 1;
			}

			// Users by role
			$usersByRole = User::with('role')
				->whereBetween('created_at', [$startDate, $endDate])
				->get()
				->groupBy('role.name')
				->map->count();

			// Users by status
			$usersByStatus = User::selectRaw('user_status, COUNT(*) as count')
				->whereBetween('created_at', [$startDate, $endDate])
				->groupBy('user_status')
				->pluck('count', 'user_status')
				->toArray();

			return [
				'trend' => [
					'labels' => array_keys($trend),
					'data' => array_values($trend),
				],
				'by_role' => [
					'labels' => $usersByRole->keys()->toArray(),
					'data' => $usersByRole->values()->toArray(),
				],
				'by_status' => [
					'labels' => array_map(function ($status) {
						return $status == 1 ? 'Active' : 'Inactive';
					}, array_keys($usersByStatus)),
					'data' => array_values($usersByStatus),
				],
				'summary' => [
					'total' => $users->count(),
					'active' => $users->where('user_status', 1)->count(),
					'inactive' => $users->where('user_status', 0)->count(),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve user registration report: ' . $e->getMessage());
		}
	}

	/**
	 * Get user engagement report
	 * 
	 * @param array $filters
	 * @return array
	 */
	public function getUserEngagementReport(array $filters = []): array
	{
		return [
			'active_users' => [],
			'activity_timeline' => [
				'labels' => [],
				'data' => [],
			],
			'summary' => [
				'active_7d' => 0,
				'active_30d' => 0,
				'active_90d' => 0,
			],
		];
	}

	/**
	 * Get user demographics report
	 * 
	 * @return array
	 */
	public function getUserDemographicsReport(): array
	{
		try {
			// Users by role - exclude Developer Account role
			$usersByRole = User::with('role')
				->whereHas('role', function ($query) {
					$query->where('name', '!=', 'Developer Account');
				})
				->get()
				->groupBy('role.name')
				->map->count();

			// Users by status - exclude Developer Account role users
			$usersByStatus = User::whereHas('role', function ($query) {
					$query->where('name', '!=', 'Developer Account');
				})
				->selectRaw('user_status, COUNT(*) as count')
				->groupBy('user_status')
				->pluck('count', 'user_status')
				->toArray();

			return [
				'by_role' => [
					'labels' => $usersByRole->keys()->toArray(),
					'data' => $usersByRole->values()->toArray(),
				],
				'by_status' => [
					'labels' => array_map(function ($status) {
						return $status == 1 ? 'Active' : 'Inactive';
					}, array_keys($usersByStatus)),
					'data' => array_values($usersByStatus),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve user demographics report: ' . $e->getMessage());
		}
	}
}

