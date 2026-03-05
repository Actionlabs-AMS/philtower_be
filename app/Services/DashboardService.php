<?php

namespace App\Services;

use App\Models\User;
use App\Models\MediaLibrary;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Page;
use Carbon\Carbon;

class DashboardService
{
	protected $userActivityService;

	public function __construct(UserActivityService $userActivityService = null)
	{
		$this->userActivityService = $userActivityService;
	}

	/**
	 * Get dashboard statistics
	 * 
	 * @return array
	 */
	public function getStats(): array
	{
		try {
			return [
				'total_users' => User::count(),
				'total_media' => MediaLibrary::count(),
				'total_categories' => Category::count(),
				'total_tags' => Tag::count(),
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve dashboard statistics: ' . $e->getMessage());
		}
	}

	/**
	 * Get enhanced dashboard statistics with more metrics
	 * 
	 * @return array
	 */
	public function getEnhancedStats(): array
	{
		try {
			$now = Carbon::now();
			$today = $now->copy()->startOfDay();
			$thisWeek = $now->copy()->startOfWeek();
			$thisMonth = $now->copy()->startOfMonth();
			$last30Days = $now->copy()->subDays(30);

			return [
				// User metrics
				'total_users' => User::count(),
				'active_users' => User::where('user_status', 1)->count(),
				'new_users_today' => User::whereDate('created_at', $today)->count(),
				'new_users_this_week' => User::where('created_at', '>=', $thisWeek)->count(),
				'new_users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
				'users_last_30_days' => User::where('created_at', '>=', $last30Days)->count(),

				// Content metrics
				'total_pages' => Page::count(),
				'published_pages' => Page::where('status', 'published')->count(),
				'draft_pages' => Page::where('status', 'draft')->count(),
				'pages_this_month' => Page::where('created_at', '>=', $thisMonth)->count(),

				// Media metrics
				'total_media' => MediaLibrary::count(),
				'media_this_month' => MediaLibrary::where('created_at', '>=', $thisMonth)->count(),

				// Category and tag metrics
				'total_categories' => Category::count(),
				'total_tags' => Tag::count(),

				// Activity metrics (if ActivityLog table exists)
				'activities_today' => $this->getActivitiesCount($today, $now),
				'activities_this_week' => $this->getActivitiesCount($thisWeek, $now),
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve enhanced dashboard statistics: ' . $e->getMessage());
		}
	}

	/**
	 * Get user registration trend
	 * 
	 * @param string $period daily|weekly|monthly
	 * @param int $days Number of days to look back
	 * @return array
	 */
	public function getUserRegistrationTrend(string $period = 'daily', int $days = 30): array
	{
		try {
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
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve user registration trend: ' . $e->getMessage());
		}
	}

	/**
	 * Get pages by status
	 * 
	 * @return array
	 */
	public function getPagesByStatus(): array
	{
		try {
			$statuses = Page::selectRaw('status, COUNT(*) as count')
				->groupBy('status')
				->pluck('count', 'status')
				->toArray();

			return [
				'labels' => array_keys($statuses),
				'data' => array_values($statuses),
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve pages by status: ' . $e->getMessage());
		}
	}

	/**
	 * Get top categories
	 * 
	 * @param int $limit
	 * @return array
	 */
	public function getTopCategories(int $limit = 10): array
	{
		try {
			// This would need a pivot table to count pages per category
			// For now, return category names
			$categories = Category::where('active', true)
				->limit($limit)
				->pluck('name')
				->toArray();

			return [
				'labels' => $categories,
				'data' => array_fill(0, count($categories), 0), // Placeholder
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve top categories: ' . $e->getMessage());
		}
	}

	/**
	 * Get activities count
	 * 
	 * @param Carbon $startDate
	 * @param Carbon $endDate
	 * @return int
	 */
	private function getActivitiesCount(Carbon $startDate, Carbon $endDate): int
	{
		return 0;
	}
}