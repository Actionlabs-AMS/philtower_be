<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AuditTrailController extends Controller
{
	protected $userActivityService;

	public function __construct(UserActivityService $userActivityService)
	{
		$this->userActivityService = $userActivityService;
	}

	/**
	 * Get audit logs for a specific date
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function getLogsForDate(Request $request): JsonResponse
	{
		try {
			$date = $request->input('date', Carbon::now()->format('Y-m-d'));
			$perPage = $request->input('per_page', 50);
			$page = $request->input('page', 1);
			$search = $request->input('search');

			// Validate date format
			if (!Carbon::createFromFormat('Y-m-d', $date)) {
				return response()->json([
					'error' => 'Invalid date format. Use Y-m-d format.'
				], 400);
			}

			$allLogs = $this->getLogsFromFiles($date, $date);

			// Format timestamps
			$allLogs = array_map(function ($log) {
				if (isset($log['timestamp'])) {
					try {
						$carbon = Carbon::parse($log['timestamp']);
						$log['timestamp_readable'] = $carbon->format('M j, Y g:i A');
					} catch (\Exception $e) {
						// Keep original timestamp if parsing fails
					}
				}
				// Map user_id to user_login if available
				if (isset($log['data']['user_login'])) {
					$log['user_login'] = $log['data']['user_login'];
				} elseif (isset($log['user_email'])) {
					$log['user_login'] = $log['user_email'];
				}
				return $log;
			}, $allLogs);

			// Apply search filter
			if ($search) {
				$allLogs = array_filter($allLogs, function ($log) use ($search) {
					$searchIn = json_encode($log);
					return stripos($searchIn, $search) !== false;
				});
			}

			// Sort by timestamp descending
			usort($allLogs, function ($a, $b) {
				$timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
				$timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
				return $timeB - $timeA;
			});

			// Apply pagination
			$total = count($allLogs);
			$offset = ($page - 1) * $perPage;
			$paginatedLogs = array_slice($allLogs, $offset, $perPage);

			$lastPage = ceil($total / $perPage);

			// Generate pagination links for DataTable component
			$links = [];
			
			// Previous link
			if ($page > 1) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page - 1])),
					'label' => '&laquo; Previous',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => '&laquo; Previous',
					'active' => false
				];
			}
			
			// Page number links
			$startPage = max(1, $page - 2);
			$endPage = min($lastPage, $page + 2);
			
			for ($i = $startPage; $i <= $endPage; $i++) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $i])),
					'label' => (string)$i,
					'active' => $i === (int)$page
				];
			}
			
			// Next link
			if ($page < $lastPage) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page + 1])),
					'label' => 'Next &raquo;',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => 'Next &raquo;',
					'active' => false
				];
			}

			return response()->json([
				'data' => array_values($paginatedLogs),
				'meta' => [
					'current_page' => (int)$page,
					'from' => (int)($offset + 1),
					'last_page' => (int)$lastPage,
					'per_page' => (int)$perPage,
					'to' => (int)min($offset + $perPage, $total),
					'total' => (int)$total,
					'all' => (int)$total,
					'trashed' => 0,
					'links' => $links
				]
			]);
		} catch (\Exception $e) {
			return response()->json([
				'error' => 'Failed to retrieve audit logs: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Get audit logs for a date range
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function getLogsForDateRange(Request $request): JsonResponse
	{
		try {
			$startDate = $request->input('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
			$endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
			$perPage = $request->input('per_page', 50);
			$page = $request->input('page', 1);
			$search = $request->input('search');

			// Validate dates
			if (!Carbon::createFromFormat('Y-m-d', $startDate) || !Carbon::createFromFormat('Y-m-d', $endDate)) {
				return response()->json([
					'error' => 'Invalid date format. Use Y-m-d format.'
				], 400);
			}

			$allLogs = $this->getLogsFromFiles($startDate, $endDate);

			// Format timestamps
			$allLogs = array_map(function ($log) {
				if (isset($log['timestamp'])) {
					try {
						$carbon = Carbon::parse($log['timestamp']);
						$log['timestamp_readable'] = $carbon->format('M j, Y g:i A');
					} catch (\Exception $e) {
						// Keep original timestamp if parsing fails
					}
				}
				// Map user_id to user_login if available
				if (isset($log['data']['user_login'])) {
					$log['user_login'] = $log['data']['user_login'];
				} elseif (isset($log['user_email'])) {
					$log['user_login'] = $log['user_email'];
				}
				return $log;
			}, $allLogs);

			// Apply search filter
			if ($search) {
				$allLogs = array_filter($allLogs, function ($log) use ($search) {
					$searchIn = json_encode($log);
					return stripos($searchIn, $search) !== false;
				});
			}

			// Sort by timestamp descending
			usort($allLogs, function ($a, $b) {
				$timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
				$timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
				return $timeB - $timeA;
			});

			// Apply pagination
			$total = count($allLogs);
			$offset = ($page - 1) * $perPage;
			$paginatedLogs = array_slice($allLogs, $offset, $perPage);

			$lastPage = ceil($total / $perPage);

			// Generate pagination links for DataTable component
			$links = [];
			
			// Previous link
			if ($page > 1) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page - 1])),
					'label' => '&laquo; Previous',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => '&laquo; Previous',
					'active' => false
				];
			}
			
			// Page number links
			$startPage = max(1, $page - 2);
			$endPage = min($lastPage, $page + 2);
			
			for ($i = $startPage; $i <= $endPage; $i++) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $i])),
					'label' => (string)$i,
					'active' => $i === (int)$page
				];
			}
			
			// Next link
			if ($page < $lastPage) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page + 1])),
					'label' => 'Next &raquo;',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => 'Next &raquo;',
					'active' => false
				];
			}

			return response()->json([
				'data' => array_values($paginatedLogs),
				'meta' => [
					'current_page' => (int)$page,
					'from' => (int)($offset + 1),
					'last_page' => (int)$lastPage,
					'per_page' => (int)$perPage,
					'to' => (int)min($offset + $perPage, $total),
					'total' => (int)$total,
					'all' => (int)$total,
					'trashed' => 0,
					'links' => $links
				]
			]);
		} catch (\Exception $e) {
			return response()->json([
				'error' => 'Failed to retrieve audit logs: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Search audit logs with filters
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function searchLogs(Request $request): JsonResponse
	{
		try {
			$userId = $request->input('user_id');
			$module = $request->input('module');
			$action = $request->input('action');
			$keyword = $request->input('keyword');
			$perPage = $request->input('per_page', 50);
			$page = $request->input('page', 1);

			// Get logs from last 90 days for search
			$startDate = Carbon::now()->subDays(90)->format('Y-m-d');
			$endDate = Carbon::now()->format('Y-m-d');

			$allLogs = $this->getLogsFromFiles($startDate, $endDate);

			// Format timestamps
			$allLogs = array_map(function ($log) {
				if (isset($log['timestamp'])) {
					try {
						$carbon = Carbon::parse($log['timestamp']);
						$log['timestamp_readable'] = $carbon->format('M j, Y g:i A');
					} catch (\Exception $e) {
						// Keep original timestamp if parsing fails
					}
				}
				// Map user_id to user_login if available
				if (isset($log['data']['user_login'])) {
					$log['user_login'] = $log['data']['user_login'];
				} elseif (isset($log['user_email'])) {
					$log['user_login'] = $log['user_email'];
				}
				return $log;
			}, $allLogs);

			// Apply filters
			$filteredLogs = array_filter($allLogs, function ($log) use ($userId, $module, $action, $keyword) {
				// Filter by user_id
				if ($userId && ($log['user_id'] ?? null) != $userId) {
					return false;
				}

				// Filter by module
				if ($module && ($log['module'] ?? '') !== $module) {
					return false;
				}

				// Filter by action
				if ($action && ($log['action'] ?? '') !== $action) {
					return false;
				}

				// Filter by keyword
				if ($keyword) {
					$searchIn = json_encode($log);
					if (stripos($searchIn, $keyword) === false) {
						return false;
					}
				}

				return true;
			});

			// Sort by timestamp descending
			usort($filteredLogs, function ($a, $b) {
				$timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
				$timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
				return $timeB - $timeA;
			});

			// Apply pagination
			$total = count($filteredLogs);
			$offset = ($page - 1) * $perPage;
			$paginatedLogs = array_slice($filteredLogs, $offset, $perPage);

			$lastPage = ceil($total / $perPage);

			// Generate pagination links for DataTable component
			$links = [];
			
			// Previous link
			if ($page > 1) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page - 1])),
					'label' => '&laquo; Previous',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => '&laquo; Previous',
					'active' => false
				];
			}
			
			// Page number links
			$startPage = max(1, $page - 2);
			$endPage = min($lastPage, $page + 2);
			
			for ($i = $startPage; $i <= $endPage; $i++) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $i])),
					'label' => (string)$i,
					'active' => $i === (int)$page
				];
			}
			
			// Next link
			if ($page < $lastPage) {
				$links[] = [
					'url' => request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $page + 1])),
					'label' => 'Next &raquo;',
					'active' => false
				];
			} else {
				$links[] = [
					'url' => null,
					'label' => 'Next &raquo;',
					'active' => false
				];
			}

			return response()->json([
				'data' => array_values($paginatedLogs),
				'meta' => [
					'current_page' => (int)$page,
					'from' => (int)($offset + 1),
					'last_page' => (int)$lastPage,
					'per_page' => (int)$perPage,
					'to' => (int)min($offset + $perPage, $total),
					'total' => (int)$total,
					'all' => (int)$total,
					'trashed' => 0,
					'links' => $links
				]
			]);
		} catch (\Exception $e) {
			return response()->json([
				'error' => 'Failed to search audit logs: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Get logs from files for a date range
	 *
	 * @param string $startDate
	 * @param string $endDate
	 * @return array
	 */
	private function getLogsFromFiles(string $startDate, string $endDate): array
	{
		$logPath = storage_path('logs');
		$allLogs = [];

		// Get main audit log file
		$mainLog = $logPath . '/audit.log';
		if (File::exists($mainLog)) {
			$logs = $this->parseLogFile($mainLog, $startDate, $endDate);
			$allLogs = array_merge($allLogs, $logs);
		}

		// Get daily log files
		$allFiles = File::files($logPath);
		foreach ($allFiles as $file) {
			$filename = $file->getFilename();
			if (preg_match('/^audit-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
				$fileDate = str_replace(['audit-', '.log'], '', $filename);

				// Filter by date range
				if ($fileDate < $startDate || $fileDate > $endDate) {
					continue;
				}

				$logs = $this->parseLogFile($file->getPathname(), $startDate, $endDate);
				$allLogs = array_merge($allLogs, $logs);
			}
		}

		return $allLogs;
	}

	/**
	 * Parse a log file
	 *
	 * @param string $filePath
	 * @param string $startDate
	 * @param string $endDate
	 * @return array
	 */
	private function parseLogFile(string $filePath, string $startDate, string $endDate): array
	{
		$logs = [];

		if (!File::exists($filePath)) {
			return $logs;
		}

		$content = File::get($filePath);
		$lines = explode("\n", $content);

		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}

			// Extract JSON from the line
			$jsonStart = strpos($line, '{');
			if ($jsonStart === false) {
				// Try to parse entire line as JSON
				$log = json_decode($line, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($log)) {
					// Filter by date range
					if (isset($log['timestamp'])) {
						$logDate = Carbon::parse($log['timestamp'])->format('Y-m-d');
						if ($logDate >= $startDate && $logDate <= $endDate) {
							$logs[] = $log;
						}
					}
				}
				continue;
			}

			// Extract JSON portion
			$jsonString = substr($line, $jsonStart);
			$log = json_decode($jsonString, true);

			if (json_last_error() === JSON_ERROR_NONE && is_array($log)) {
				// Verify this is an audit trail log
				if (isset($log['module']) && isset($log['action'])) {
					// Filter by date range
					if (isset($log['timestamp'])) {
						$logDate = Carbon::parse($log['timestamp'])->format('Y-m-d');
						if ($logDate >= $startDate && $logDate <= $endDate) {
							$logs[] = $log;
						}
					}
				}
			}
		}

		return $logs;
	}
}

