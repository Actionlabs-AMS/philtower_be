<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\User;
use App\Models\Page;
use App\Models\Category;
use App\Models\Tag;
use App\Models\MediaLibrary;
use Carbon\Carbon;

class WidgetDataService
{
	protected $dashboardService;
	protected $userActivityService;

	public function __construct(
		DashboardService $dashboardService,
		UserActivityService $userActivityService = null
	) {
		$this->dashboardService = $dashboardService;
		$this->userActivityService = $userActivityService;
	}

	/**
	 * Get widget data based on widget configuration
	 * 
	 * @param DashboardWidget $widget
	 * @return array
	 */
	public function getWidgetData(DashboardWidget $widget): array
	{
		$dataSource = $widget->data_source;
		$queryConfig = $widget->query_config ?? [];
		$widgetType = $widget->widget_type;

		try {
			switch ($dataSource) {
				case 'users':
					return $this->getUserWidgetData($widgetType, $queryConfig);
				case 'pages':
					return $this->getPageWidgetData($widgetType, $queryConfig);
				case 'categories':
					return $this->getCategoryWidgetData($widgetType, $queryConfig);
				case 'tags':
					return $this->getTagWidgetData($widgetType, $queryConfig);
				case 'media':
					return $this->getMediaWidgetData($widgetType, $queryConfig);
				case 'activities':
					return $this->getActivityWidgetData($widgetType, $queryConfig);
				case 'dashboard_stats':
					return $this->getDashboardStatsWidgetData($widgetType, $queryConfig);
				default:
					return $this->getDefaultWidgetData($widget);
			}
		} catch (\Exception $e) {
			return [
				'error' => true,
				'message' => 'Failed to retrieve widget data: ' . $e->getMessage(),
				'data' => [],
			];
		}
	}

	/**
	 * Get user widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getUserWidgetData(string $widgetType, array $queryConfig): array
	{
		switch ($widgetType) {
			case 'metric':
				$metric = $queryConfig['metric'] ?? 'total';
				$value = match ($metric) {
					'total' => User::count(),
					'active' => User::where('user_status', 1)->count(),
					'new_today' => User::whereDate('created_at', today())->count(),
					'new_this_week' => User::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
					'new_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
					default => User::count(),
				};
				return ['value' => $value];

			case 'line_chart':
				$period = $queryConfig['period'] ?? 'daily';
				$days = $queryConfig['days'] ?? 30;
				return $this->dashboardService->getUserRegistrationTrend($period, $days);

			case 'pie_chart':
				// Users by role
				$usersByRole = User::with('role')
					->get()
					->groupBy('role.name')
					->map->count();
				return [
					'labels' => $usersByRole->keys()->toArray(),
					'data' => $usersByRole->values()->toArray(),
				];

			default:
				return ['data' => []];
		}
	}

	/**
	 * Get page widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getPageWidgetData(string $widgetType, array $queryConfig): array
	{
		switch ($widgetType) {
			case 'metric':
				$metric = $queryConfig['metric'] ?? 'total';
				$value = match ($metric) {
					'total' => Page::count(),
					'published' => Page::where('status', 'published')->count(),
					'draft' => Page::where('status', 'draft')->count(),
					default => Page::count(),
				};
				return ['value' => $value];

			case 'pie_chart':
				return $this->dashboardService->getPagesByStatus();

			default:
				return ['data' => []];
		}
	}

	/**
	 * Get category widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getCategoryWidgetData(string $widgetType, array $queryConfig): array
	{
		switch ($widgetType) {
			case 'metric':
				return ['value' => Category::count()];

			case 'bar_chart':
				$limit = $queryConfig['limit'] ?? 10;
				return $this->dashboardService->getTopCategories($limit);

			default:
				return ['data' => []];
		}
	}

	/**
	 * Get tag widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getTagWidgetData(string $widgetType, array $queryConfig): array
	{
		switch ($widgetType) {
			case 'metric':
				return ['value' => Tag::count()];

			default:
				return ['data' => []];
		}
	}

	/**
	 * Get media widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getMediaWidgetData(string $widgetType, array $queryConfig): array
	{
		switch ($widgetType) {
			case 'metric':
				$metric = $queryConfig['metric'] ?? 'total';
				$value = match ($metric) {
					'total' => MediaLibrary::count(),
					'total_size' => MediaLibrary::sum('file_size'),
					default => MediaLibrary::count(),
				};
				return ['value' => $value];

			case 'pie_chart':
				$mediaByType = MediaLibrary::selectRaw('file_type, COUNT(*) as count')
					->groupBy('file_type')
					->pluck('count', 'file_type')
					->toArray();
				return [
					'labels' => array_keys($mediaByType),
					'data' => array_values($mediaByType),
				];

			default:
				return ['data' => []];
		}
	}

	/**
	 * Get activity widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getActivityWidgetData(string $widgetType, array $queryConfig): array
	{
		return ['data' => []];
	}

	/**
	 * Get dashboard stats widget data
	 * 
	 * @param string $widgetType
	 * @param array $queryConfig
	 * @return array
	 */
	private function getDashboardStatsWidgetData(string $widgetType, array $queryConfig): array
	{
		if ($widgetType === 'metric') {
			$stats = $this->dashboardService->getEnhancedStats();
			$metric = $queryConfig['metric'] ?? 'total_users';
			return ['value' => $stats[$metric] ?? 0];
		}
		return ['data' => []];
	}

	/**
	 * Get default widget data
	 * 
	 * @param DashboardWidget $widget
	 * @return array
	 */
	private function getDefaultWidgetData(DashboardWidget $widget): array
	{
		return [
			'data' => [],
			'message' => 'Widget data source not configured',
		];
	}
}

