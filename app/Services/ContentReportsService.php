<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Category;
use App\Models\Tag;
use App\Models\MediaLibrary;
use App\Models\User;
use Carbon\Carbon;

class ContentReportsService
{
	/**
	 * Get content performance report
	 * 
	 * @param array $filters
	 * @return array
	 */
	public function getContentPerformanceReport(array $filters = []): array
	{
		try {
			$startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
			$endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

			// Pages by status
			$pagesByStatus = Page::selectRaw('status, COUNT(*) as count')
				->whereBetween('created_at', [$startDate, $endDate])
				->groupBy('status')
				->pluck('count', 'status')
				->toArray();

			// Pages by author
			$pagesByAuthor = Page::with('author')
				->whereBetween('created_at', [$startDate, $endDate])
				->get()
				->groupBy('author.user_login')
				->map->count()
				->sortDesc()
				->take(10);

			// Publishing trend
			$publishingTrend = Page::whereBetween('created_at', [$startDate, $endDate])
				->where('status', 'published')
				->selectRaw('DATE(created_at) as date, COUNT(*) as count')
				->groupBy('date')
				->orderBy('date', 'asc')
				->get();

			$trend = [];
			foreach ($publishingTrend as $item) {
				$trend[$item->date] = $item->count;
			}

			// Content creation timeline
			$creationTrend = Page::whereBetween('created_at', [$startDate, $endDate])
				->selectRaw('DATE(created_at) as date, COUNT(*) as count')
				->groupBy('date')
				->orderBy('date', 'asc')
				->get();

			$creation = [];
			foreach ($creationTrend as $item) {
				$creation[$item->date] = $item->count;
			}

			return [
				'by_status' => [
					'labels' => array_keys($pagesByStatus),
					'data' => array_values($pagesByStatus),
				],
				'by_author' => [
					'labels' => $pagesByAuthor->keys()->toArray(),
					'data' => $pagesByAuthor->values()->toArray(),
				],
				'publishing_trend' => [
					'labels' => array_keys($trend),
					'data' => array_values($trend),
				],
				'creation_trend' => [
					'labels' => array_keys($creation),
					'data' => array_values($creation),
				],
				'summary' => [
					'total_pages' => Page::whereBetween('created_at', [$startDate, $endDate])->count(),
					'published' => Page::where('status', 'published')
						->whereBetween('created_at', [$startDate, $endDate])
						->count(),
					'draft' => Page::where('status', 'draft')
						->whereBetween('created_at', [$startDate, $endDate])
						->count(),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve content performance report: ' . $e->getMessage());
		}
	}

	/**
	 * Get category analysis report
	 * 
	 * @return array
	 */
	public function getCategoryAnalysisReport(): array
	{
		try {
			// Since there's no direct pages relationship, get all categories
			$categories = Category::orderBy('name', 'asc')
				->get();

			return [
				'categories' => $categories->map(function ($category) {
					return [
						'id' => $category->id,
						'name' => $category->name,
						'page_count' => 0, // Placeholder - would need pivot table
						'active' => $category->active,
					];
				})->toArray(),
				'summary' => [
					'total_categories' => Category::count(),
					'active_categories' => Category::where('active', true)->count(),
					'total_pages' => Page::count(),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve category analysis report: ' . $e->getMessage());
		}
	}

	/**
	 * Get tag analysis report
	 * 
	 * @return array
	 */
	public function getTagAnalysisReport(): array
	{
		try {
			// Since there's no direct pages relationship, get all tags
			$tags = Tag::orderBy('name', 'asc')
				->get();

			return [
				'tags' => $tags->map(function ($tag) {
					return [
						'id' => $tag->id,
						'name' => $tag->name,
						'page_count' => 0, // Placeholder - would need pivot table
						'color' => $tag->color ?? '#007bff',
						'active' => $tag->active ?? true,
					];
				})->toArray(),
				'summary' => [
					'total_tags' => Tag::count(),
					'active_tags' => Tag::where('active', true)->count(),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve tag analysis report: ' . $e->getMessage());
		}
	}

	/**
	 * Get media library report
	 * 
	 * @param array $filters
	 * @return array
	 */
	public function getMediaLibraryReport(array $filters = []): array
	{
		try {
			$startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
			$endDate = $filters['end_date'] ?? Carbon::now()->toDateString();

			// Files by type
			$filesByType = MediaLibrary::selectRaw('file_type, COUNT(*) as count')
				->whereBetween('created_at', [$startDate, $endDate])
				->groupBy('file_type')
				->pluck('count', 'file_type')
				->toArray();

			// Upload trend
			$uploadTrend = MediaLibrary::whereBetween('created_at', [$startDate, $endDate])
				->selectRaw('DATE(created_at) as date, COUNT(*) as count')
				->groupBy('date')
				->orderBy('date', 'asc')
				->get();

			$trend = [];
			foreach ($uploadTrend as $item) {
				$trend[$item->date] = $item->count;
			}

			// Total storage (if file_size is numeric)
			$totalSize = MediaLibrary::whereBetween('created_at', [$startDate, $endDate])
				->sum('file_size');

			return [
				'by_type' => [
					'labels' => array_keys($filesByType),
					'data' => array_values($filesByType),
				],
				'upload_trend' => [
					'labels' => array_keys($trend),
					'data' => array_values($trend),
				],
				'summary' => [
					'total_files' => MediaLibrary::whereBetween('created_at', [$startDate, $endDate])->count(),
					'total_size' => $totalSize,
					'average_size' => MediaLibrary::whereBetween('created_at', [$startDate, $endDate])->avg('file_size'),
				],
			];
		} catch (\Exception $e) {
			throw new \Exception('Failed to retrieve media library report: ' . $e->getMessage());
		}
	}
}

