<?php

namespace App\Services;

use App\Models\User;
use App\Models\Page;
use App\Models\Category;
use App\Models\Tag;
use App\Models\MediaLibrary;
use Carbon\Carbon;

class AnalyticsService
{
	protected $userActivityService;

	public function __construct(UserActivityService $userActivityService = null)
	{
		$this->userActivityService = $userActivityService;
	}

	/**
	 * Get analytics overview data
	 * 
	 * @return array
	 */
	public function getAnalyticsOverview(): array
	{
		try {
			$now = Carbon::now();
			$today = $now->copy()->startOfDay();
			$thisWeek = $now->copy()->startOfWeek();
			$thisMonth = $now->copy()->startOfMonth();
			$last30Days = $now->copy()->subDays(30);
			$last7Days = $now->copy()->subDays(7);

			return [
				'summary' => [
					'total_users' => User::count(),
					'total_pages' => Page::count(),
					'total_media' => MediaLibrary::count(),
					'total_categories' => Category::count(),
					'total_tags' => Tag::count(),
				],
				'user_metrics' => [
					'total' => User::count(),
					'active' => User::where('user_status', 1)->count(),
					'new_today' => User::whereDate('created_at', $today)->count(),
					'new_this_week' => User::where('created_at', '>=', $thisWeek)->count(),
					'new_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
					'new_last_30_days' => User::where('created_at', '>=', $last30Days)->count(),
				],
				'content_metrics' => [
					'total_pages' => Page::count(),
					'published_pages' => Page::where('status', 'published')->count(),
					'draft_pages' => Page::where('status', 'draft')->count(),
					'pages_this_month' => Page::where('created_at', '>=', $thisMonth)->count(),
				],
				'activity_metrics' => $this->getActivityMetrics($last7Days, $now),
				'trends' => [
					'user_registration' => $this->getUserRegistrationTrend('daily', 30),
					'page_creation' => $this->getPageCreationTrend('daily', 30),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve analytics overview: ' . $e->getMessage());
		}
	}

	/**
	 * Get activity metrics
	 * 
	 * @param Carbon $startDate
	 * @param Carbon $endDate
	 * @return array
	 */
	private function getActivityMetrics(Carbon $startDate, Carbon $endDate): array
	{
		return [
			'total' => 0,
			'today' => 0,
			'this_week' => 0,
			'by_module' => [],
			'by_action' => [],
		];
	}

	/**
	 * Get user registration trend
	 * 
	 * @param string $period
	 * @param int $days
	 * @return array
	 */
	private function getUserRegistrationTrend(string $period = 'daily', int $days = 30): array
	{
		$startDate = Carbon::now()->subDays($days)->startOfDay();
		$users = User::where('created_at', '>=', $startDate)
			->orderBy('created_at', 'asc')
			->get(['created_at']);

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

		return [
			'labels' => array_keys($trend),
			'data' => array_values($trend),
		];
	}

	/**
	 * Get page creation trend
	 * 
	 * @param string $period
	 * @param int $days
	 * @return array
	 */
	private function getPageCreationTrend(string $period = 'daily', int $days = 30): array
	{
		$startDate = Carbon::now()->subDays($days)->startOfDay();
		$pages = Page::where('created_at', '>=', $startDate)
			->orderBy('created_at', 'asc')
			->get(['created_at']);

		$trend = [];
		$format = match ($period) {
			'weekly' => 'Y-W',
			'monthly' => 'Y-m',
			default => 'Y-m-d',
		};

		foreach ($pages as $page) {
			$key = $page->created_at->format($format);
			$trend[$key] = ($trend[$key] ?? 0) + 1;
		}

		return [
			'labels' => array_keys($trend),
			'data' => array_values($trend),
		];
	}
}

