<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class UserActivityService
{
	/**
	 * Get all activities for a specific user
	 * Uses database first, falls back to file logs if needed
	 *
	 * @param int $userId
	 * @param array $filters
	 * @return array
	 */
	public function getUserActivities(int $userId, array $filters = []): array
	{
		return $this->getUserActivitiesFromFiles($userId, $filters);
	}

	/**
	 * Get activities from file logs (original implementation)
	 *
	 * @param int $userId
	 * @param array $filters
	 * @return array
	 */
	private function getUserActivitiesFromFiles(int $userId, array $filters = []): array
	{
		$activities = [];
		
		// Get date range filter
		$startDate = $filters['start_date'] ?? null;
		$endDate = $filters['end_date'] ?? null;
		$module = $filters['module'] ?? null;
		$action = $filters['action'] ?? null;
		$limit = $filters['limit'] ?? 100;
		
		// Get audit log files
		$logFiles = $this->getAuditLogFiles($startDate, $endDate);
		
		// Log for debugging
		\Log::info('UserActivityService: Looking for activities', [
            'user_id' => $userId,
            'log_files_found' => count($logFiles),
            'log_files' => $logFiles,
        ]);
        
        // Parse each log file
        foreach ($logFiles as $file) {
            $logs = $this->parseLogFile($file);
            
            \Log::info('UserActivityService: Parsed log file', [
                'file' => $file,
                'total_logs' => count($logs),
            ]);
            
            // Filter by user_id
            foreach ($logs as $log) {
                // Check if user_id exists and matches
                // user_id can be at root level or in data array
                $logUserId = $log['user_id'] ?? $log['data']['user_id'] ?? null;
                
                // Handle both string and integer user_id
                if ($logUserId === null || (int)$logUserId !== (int)$userId) {
                    continue;
                }
                
                // Filter by date range if provided
                if ($startDate || $endDate) {
                    $logTimestamp = isset($log['timestamp']) ? $log['timestamp'] : null;
                    if ($logTimestamp) {
                        try {
                            $logDate = Carbon::parse($logTimestamp)->format('Y-m-d');
                            
                            if ($startDate && $logDate < $startDate) {
                                continue;
                            }
                            if ($endDate && $logDate > $endDate) {
                                continue;
                            }
                        } catch (\Exception $e) {
                            // Skip if timestamp parsing fails
                            continue;
                        }
                    }
                }
                
                // Apply additional filters
                if ($module && isset($log['module']) && $log['module'] !== $module) {
                    continue;
                }
                
                if ($action) {
                    if (is_array($action)) {
                        if (!isset($log['action']) || !in_array($log['action'], $action)) {
                            continue;
                        }
                    } else {
                        if (!isset($log['action']) || $log['action'] !== $action) {
                            continue;
                        }
                    }
                }
                
                $activities[] = $log;
            }
        }
        
        \Log::info('UserActivityService: Found activities', [
            'user_id' => $userId,
            'total_activities' => count($activities),
        ]);
        
        // Sort by timestamp (newest first)
        usort($activities, function($a, $b) {
            $timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
            return $timeB - $timeA;
        });
        
        // Apply limit
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Get login history for a user
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getUserLoginHistory(int $userId, array $filters = []): array
    {
        $loginHistory = [];
        
        // Get from audit logs
        $activities = $this->getUserActivities($userId, [
            'module' => 'AUTHENTICATION',
            'action' => ['LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT', 'LOGIN_BLOCKED'],
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'limit' => $filters['limit'] ?? 50,
        ]);
        
        // Format login history
        foreach ($activities as $activity) {
            $loginHistory[] = [
                'id' => uniqid(),
                'type' => $this->mapActionToLoginType($activity['action'] ?? ''),
                'timestamp' => $activity['timestamp'] ?? null,
                'ip_address' => $activity['ip_address'] ?? null,
                'user_agent' => $activity['user_agent'] ?? null,
                'status' => $this->getLoginStatus($activity['action'] ?? ''),
                'reason' => $activity['data']['reason'] ?? null,
                'location' => $this->getLocationFromIP($activity['ip_address'] ?? null),
                'device_info' => $this->parseUserAgent($activity['user_agent'] ?? null),
            ];
        }
        
        // Get active sessions from Sanctum tokens
        $user = User::find($userId);
        if ($user) {
            $tokens = $user->tokens()
                ->orderBy('last_used_at', 'desc')
                ->get();
            
            foreach ($tokens as $token) {
                $loginHistory[] = [
                    'id' => 'token_' . $token->id,
                    'type' => 'session',
                    'timestamp' => $token->created_at?->toISOString(),
                    'last_activity' => $token->last_used_at?->toISOString(),
                    'ip_address' => null, // Tokens don't store IP
                    'user_agent' => null,
                    'status' => $token->expires_at && $token->expires_at->isFuture() ? 'active' : 'expired',
                    'session_id' => $token->id,
                    'expires_at' => $token->expires_at?->toISOString(),
                ];
            }
        }
        
        // Sort by timestamp
        usort($loginHistory, function($a, $b) {
            $timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
            return $timeB - $timeA;
        });
        
        return array_slice($loginHistory, 0, $filters['limit'] ?? 50);
    }

    /**
     * Login-related audit actions used for login history.
     */
    private const LOGIN_HISTORY_AUTH_ACTIONS = ['LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT', 'LOGIN_BLOCKED'];

    /**
     * Aggregate login history for all users (audit log auth events + Sanctum tokens).
     *
     * @param array{start_date?: string|null, end_date?: string|null, limit?: int} $filters
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsersLoginHistory(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $rawLimit = isset($filters['limit']) ? (int) $filters['limit'] : 10000;
        $limit = min(max($rawLimit, 1), 50000);

        $ipLocationCache = [];
        $rows = [];

        $authActions = self::LOGIN_HISTORY_AUTH_ACTIONS;
        $logFiles = $this->getAuditLogFiles($startDate, $endDate);

        foreach ($logFiles as $file) {
            $logs = $this->parseLogFile($file);
            foreach ($logs as $log) {
                if (($log['module'] ?? '') !== 'AUTHENTICATION') {
                    continue;
                }
                $action = $log['action'] ?? '';
                if (! in_array($action, $authActions, true)) {
                    continue;
                }

                $logUserId = $log['user_id'] ?? $log['data']['user_id'] ?? null;
                if ($logUserId === null) {
                    continue;
                }
                $userId = (int) $logUserId;

                if ($startDate || $endDate) {
                    $logTimestamp = $log['timestamp'] ?? null;
                    if ($logTimestamp) {
                        try {
                            $logDate = Carbon::parse($logTimestamp)->format('Y-m-d');
                            if ($startDate && $logDate < $startDate) {
                                continue;
                            }
                            if ($endDate && $logDate > $endDate) {
                                continue;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                $ip = $log['ip_address'] ?? null;
                if ($ip !== null && $ip !== '' && ! array_key_exists($ip, $ipLocationCache)) {
                    $ipLocationCache[$ip] = $this->getLocationFromIP($ip);
                }
                $location = ($ip !== null && $ip !== '') ? ($ipLocationCache[$ip] ?? null) : null;

                $userAgent = $log['user_agent'] ?? null;
                $deviceInfo = $this->parseUserAgent($userAgent);

                $rows[] = [
                    'id' => uniqid('', true),
                    'user_id' => $userId,
                    'type' => $this->mapActionToLoginType($action),
                    'timestamp' => $log['timestamp'] ?? null,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'status' => $this->getLoginStatus($action),
                    'reason' => $log['data']['reason'] ?? null,
                    'location' => $location,
                    'device_info' => $deviceInfo,
                    'last_activity' => null,
                    'expires_at' => null,
                ];
            }
        }

        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($tokens as $token) {
            $userId = (int) $token->tokenable_id;
            $createdAt = $token->created_at?->toISOString();
            $lastUsed = $token->last_used_at?->toISOString();
            $expiresAt = $token->expires_at?->toISOString();

            if ($startDate || $endDate) {
                if ($createdAt) {
                    try {
                        $logDate = Carbon::parse($createdAt)->format('Y-m-d');
                        if ($startDate && $logDate < $startDate) {
                            continue;
                        }
                        if ($endDate && $logDate > $endDate) {
                            continue;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            $rows[] = [
                'id' => 'token_'.$token->id,
                'user_id' => $userId,
                'type' => 'session',
                'timestamp' => $createdAt,
                'ip_address' => null,
                'user_agent' => null,
                'status' => $token->expires_at && $token->expires_at->isFuture() ? 'active' : 'expired',
                'reason' => null,
                'location' => null,
                'device_info' => null,
                'last_activity' => $lastUsed,
                'expires_at' => $expiresAt,
            ];
        }

        $userIds = array_values(array_unique(array_column($rows, 'user_id')));
        $users = collect();
        if ($userIds !== []) {
            $users = User::query()
                ->whereIn('id', $userIds)
                ->get(['id', 'user_login', 'user_email'])
                ->keyBy('id');
        }

        foreach ($rows as &$row) {
            $u = $users->get($row['user_id']);
            $row['user_login'] = $u ? ($u->user_login ?? '') : '';
            $row['user_email'] = $u ? ($u->user_email ?? '') : '';
        }
        unset($row);

        usort($rows, function ($a, $b) {
            $timeA = isset($a['timestamp']) ? strtotime((string) $a['timestamp']) : 0;
            $timeB = isset($b['timestamp']) ? strtotime((string) $b['timestamp']) : 0;

            return $timeB <=> $timeA;
        });

        return array_slice($rows, 0, $limit);
    }
    
    /**
     * Get active sessions for a user
     *
     * @param int $userId
     * @return array
     */
    public function getActiveSessions(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }
        
        $sessions = [];
        $tokens = $user->tokens()
            ->orderBy('last_used_at', 'desc')
            ->get();
        
        foreach ($tokens as $token) {
            $sessions[] = [
                'id' => $token->id,
                'name' => $token->name ?? 'Unknown',
                'created_at' => $token->created_at?->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
                'is_active' => !$token->expires_at || $token->expires_at->isFuture(),
                'abilities' => $token->abilities ?? [],
            ];
        }
        
        return $sessions;
    }
    
    /**
     * Get activity timeline for a user
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getUserTimeline(int $userId, array $filters = []): array
    {
        $timeline = [];
        
        // Get all activities
        $activities = $this->getUserActivities($userId, [
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'limit' => $filters['limit'] ?? 200,
        ]);
        
        // Group by date
        foreach ($activities as $activity) {
            $date = isset($activity['timestamp']) 
                ? Carbon::parse($activity['timestamp'])->format('Y-m-d')
                : 'unknown';
            
            if (!isset($timeline[$date])) {
                $timeline[$date] = [];
            }
            
            $timeline[$date][] = [
                'id' => uniqid(),
                'time' => isset($activity['timestamp']) 
                    ? Carbon::parse($activity['timestamp'])->format('H:i:s')
                    : null,
                'module' => $activity['module'] ?? 'UNKNOWN',
                'action' => $activity['action'] ?? 'UNKNOWN',
                'description' => $this->formatActivityDescription($activity),
                'ip_address' => $activity['ip_address'] ?? null,
                'metadata' => $activity['data'] ?? [],
            ];
        }
        
        // Sort timeline by date (newest first)
        krsort($timeline);
        
        return $timeline;
    }
    
    /**
     * Get activity statistics for a user
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getActivityStatistics(int $userId, array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
        
        \Log::info('UserActivityService: Getting statistics', [
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        
        // For statistics, we need ALL activities without limit
        // Process activities directly without applying limit
        $activities = [];
        $startDateFilter = $startDate;
        $endDateFilter = $endDate;
        
        // Get audit log files
        $logFiles = $this->getAuditLogFiles($startDateFilter, $endDateFilter);
        
        \Log::info('UserActivityService: Processing log files for statistics', [
            'user_id' => $userId,
            'log_files_found' => count($logFiles),
        ]);
        
        // Parse each log file and count activities
        $allLogoutActivities = [];
        $allFailedLoginActivities = [];
        
        foreach ($logFiles as $file) {
            $logs = $this->parseLogFile($file);
            
            foreach ($logs as $log) {
                $logModule = $log['module'] ?? 'UNKNOWN';
                $logAction = $log['action'] ?? 'UNKNOWN';
                
                // Track ALL LOGOUT and LOGIN_FAILED activities for debugging (regardless of user_id)
                if ($logModule === 'AUTHENTICATION') {
                    if ($logAction === 'LOGOUT') {
                        $allLogoutActivities[] = [
                            'user_id' => $log['user_id'] ?? $log['data']['user_id'] ?? null,
                            'timestamp' => $log['timestamp'] ?? null,
                            'file' => basename($file),
                            'full_log' => $log,
                        ];
                    } elseif ($logAction === 'LOGIN_FAILED' || $logAction === 'LOGIN_BLOCKED') {
                        $allFailedLoginActivities[] = [
                            'user_id' => $log['user_id'] ?? $log['data']['user_id'] ?? null,
                            'timestamp' => $log['timestamp'] ?? null,
                            'file' => basename($file),
                            'action' => $logAction,
                            'full_log' => $log,
                        ];
                    }
                }
                
                // Check if user_id exists and matches
                // user_id can be at root level or in data array
                $logUserId = $log['user_id'] ?? $log['data']['user_id'] ?? null;
                
                // Debug logging for LOGOUT actions
                if ($logAction === 'LOGOUT') {
                    \Log::info('UserActivityService: Found LOGOUT activity in statistics', [
                        'target_user_id' => $userId,
                        'log_user_id' => $logUserId,
                        'log_user_id_type' => gettype($logUserId),
                        'matches' => $logUserId !== null && (int)$logUserId === (int)$userId,
                        'module' => $logModule,
                        'action' => $logAction,
                        'timestamp' => $log['timestamp'] ?? 'N/A',
                        'file' => basename($file),
                    ]);
                }
                
                // Handle both string and integer user_id
                if ($logUserId === null || (int)$logUserId !== (int)$userId) {
                    continue;
                }
                
                // Filter by date range if provided
                if ($startDateFilter || $endDateFilter) {
                    $logTimestamp = isset($log['timestamp']) ? $log['timestamp'] : null;
                    if ($logTimestamp) {
                        try {
                            $logDate = Carbon::parse($logTimestamp)->format('Y-m-d');
                            
                            if ($startDateFilter && $logDate < $startDateFilter) {
                                continue;
                            }
                            if ($endDateFilter && $logDate > $endDateFilter) {
                                continue;
                            }
                        } catch (\Exception $e) {
                            // Skip if timestamp parsing fails
                            continue;
                        }
                    }
                }
                
                $activities[] = $log;
            }
        }
        
        // Count authentication activities for debugging
        $authActivities = array_filter($activities, function($activity) {
            return ($activity['module'] ?? '') === 'AUTHENTICATION';
        });
        $logoutCount = count(array_filter($authActivities, function($activity) {
            return ($activity['action'] ?? '') === 'LOGOUT';
        }));
        $failedLoginCount = count(array_filter($authActivities, function($activity) {
            return in_array($activity['action'] ?? '', ['LOGIN_FAILED', 'LOGIN_BLOCKED']);
        }));
        
        \Log::info('UserActivityService: Activities for statistics', [
            'user_id' => $userId,
            'activities_count' => count($activities),
            'auth_activities_count' => count($authActivities),
            'logout_count_found' => $logoutCount,
            'failed_login_count_found' => $failedLoginCount,
            'all_logout_activities_found' => count($allLogoutActivities),
            'all_failed_login_activities_found' => count($allFailedLoginActivities),
            'logout_activities_by_user_id' => array_count_values(array_column($allLogoutActivities, 'user_id')),
            'failed_login_activities_by_user_id' => array_count_values(array_column($allFailedLoginActivities, 'user_id')),
            'sample_activity' => count($activities) > 0 ? [
                'module' => $activities[0]['module'] ?? 'N/A',
                'action' => $activities[0]['action'] ?? 'N/A',
                'user_id' => $activities[0]['user_id'] ?? 'N/A',
                'has_data' => isset($activities[0]['data']),
            ] : 'no activities',
        ]);
        
        $stats = [
            'total_activities' => count($activities),
            'login_count' => 0,
            'failed_login_count' => 0,
            'logout_count' => 0,
            'profile_updates' => 0,
            'api_requests' => 0,
            'by_module' => [],
            'by_action' => [],
            'by_date' => [],
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'last_updated' => now()->toISOString(),
        ];
        
        foreach ($activities as $activity) {
            $module = $activity['module'] ?? 'UNKNOWN';
            $action = $activity['action'] ?? 'UNKNOWN';
            $date = isset($activity['timestamp']) 
                ? Carbon::parse($activity['timestamp'])->format('Y-m-d')
                : 'unknown';
            
            // Debug logging for AUTHENTICATION actions
            if ($module === 'AUTHENTICATION' && in_array($action, ['LOGOUT', 'LOGIN_FAILED', 'LOGIN_BLOCKED', 'LOGIN_SUCCESS'])) {
                \Log::info('UserActivityService: Processing authentication activity in statistics', [
                    'user_id' => $userId,
                    'module' => $module,
                    'action' => $action,
                    'activity_user_id' => $activity['user_id'] ?? 'not set',
                    'activity_data_user_id' => $activity['data']['user_id'] ?? 'not set',
                    'will_count_logout' => ($module === 'AUTHENTICATION' && $action === 'LOGOUT'),
                    'will_count_failed' => ($module === 'AUTHENTICATION' && ($action === 'LOGIN_FAILED' || $action === 'LOGIN_BLOCKED')),
                ]);
            }
            
            // Count by action
            if (!isset($stats['by_action'][$action])) {
                $stats['by_action'][$action] = 0;
            }
            $stats['by_action'][$action]++;
            
            // Count by module
            if (!isset($stats['by_module'][$module])) {
                $stats['by_module'][$module] = 0;
            }
            $stats['by_module'][$module]++;
            
            // Count by date
            if (!isset($stats['by_date'][$date])) {
                $stats['by_date'][$date] = 0;
            }
            $stats['by_date'][$date]++;
            
            // Specific counts
            if ($module === 'AUTHENTICATION') {
                if ($action === 'LOGIN_SUCCESS') {
                    $stats['login_count']++;
                    \Log::debug('UserActivityService: Incremented login_count', ['current_count' => $stats['login_count']]);
                } elseif ($action === 'LOGIN_FAILED' || $action === 'LOGIN_BLOCKED') {
                    $stats['failed_login_count']++;
                    \Log::debug('UserActivityService: Incremented failed_login_count', [
                        'action' => $action,
                        'current_count' => $stats['failed_login_count']
                    ]);
                } elseif ($action === 'LOGOUT') {
                    $stats['logout_count']++;
                    \Log::debug('UserActivityService: Incremented logout_count', ['current_count' => $stats['logout_count']]);
                }
            } elseif ($module === 'USER_MANAGEMENT' && $action === 'UPDATE') {
                $stats['profile_updates']++;
            } else {
                $stats['api_requests']++;
            }
        }
        
        // Count AUTHENTICATION actions for debugging
        $authActions = [];
        foreach ($activities as $activity) {
            if (($activity['module'] ?? '') === 'AUTHENTICATION') {
                $action = $activity['action'] ?? 'UNKNOWN';
                if (!isset($authActions[$action])) {
                    $authActions[$action] = 0;
                }
                $authActions[$action]++;
            }
        }
        
        \Log::info('UserActivityService: Statistics calculated', [
            'user_id' => $userId,
            'total_activities' => $stats['total_activities'],
            'login_count' => $stats['login_count'],
            'failed_login_count' => $stats['failed_login_count'],
            'logout_count' => $stats['logout_count'],
            'profile_updates' => $stats['profile_updates'],
            'api_requests' => $stats['api_requests'],
            'modules_count' => count($stats['by_module']),
            'actions_count' => count($stats['by_action']),
            'auth_actions_breakdown' => $authActions,
            'by_action_AUTHENTICATION' => $stats['by_action'],
        ]);
        
        return $stats;
    }
    
    /**
     * Get audit log files
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getAuditLogFiles(?string $startDate = null, ?string $endDate = null): array
    {
        $logPath = storage_path('logs');
        $files = [];
        
        // Get main audit log file
        $mainLog = $logPath . '/audit.log';
        if (File::exists($mainLog)) {
            $files[] = $mainLog;
        }
        
        // Get daily log files if they exist
        $allFiles = File::files($logPath);
        foreach ($allFiles as $file) {
            $filename = $file->getFilename();
            if (preg_match('/^audit-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
                $fileDate = str_replace(['audit-', '.log'], '', $filename);
                
                // Filter by date range if provided
                if ($startDate && $fileDate < $startDate) {
                    continue;
                }
                if ($endDate && $fileDate > $endDate) {
                    continue;
                }
                
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Parse a log file
     *
     * @param string $filePath
     * @return array
     */
    private function parseLogFile(string $filePath): array
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
            
            // Laravel log format: [YYYY-MM-DD HH:MM:SS] local.INFO: Audit Trail {...JSON...}
            // Or: [YYYY-MM-DD HH:MM:SS] local.INFO: message {...JSON...}
            // Extract JSON from the line
            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) {
                // Try to parse entire line as JSON (in case it's pure JSON)
                $log = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($log)) {
                    $logs[] = $log;
                }
                continue;
            }
            
            // Extract JSON portion
            $jsonString = substr($line, $jsonStart);
            
            // Try to parse the JSON
            $log = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($log)) {
                // Verify this is an audit trail log (has module and action)
                if (isset($log['module']) && isset($log['action'])) {
                    $logs[] = $log;
                } else {
                    // Log for debugging if structure is unexpected
                    \Log::debug('UserActivityService: Skipped log entry (missing module/action)', [
                        'file' => $filePath,
                        'has_module' => isset($log['module']),
                        'has_action' => isset($log['action']),
                        'keys' => array_keys($log),
                    ]);
                }
            } else {
                // Log JSON parsing errors for debugging
                \Log::debug('UserActivityService: Failed to parse JSON', [
                    'file' => $filePath,
                    'json_error' => json_last_error_msg(),
                    'line_preview' => substr($line, 0, 200),
                ]);
            }
        }
        
        return $logs;
    }
    
    /**
     * Map action to login type
     *
     * @param string $action
     * @return string
     */
    private function mapActionToLoginType(string $action): string
    {
        $map = [
            'LOGIN_SUCCESS' => 'success',
            'LOGIN_FAILED' => 'failed',
            'LOGIN_BLOCKED' => 'blocked',
            'LOGOUT' => 'logout',
        ];
        
        return $map[$action] ?? 'unknown';
    }
    
    /**
     * Get login status
     *
     * @param string $action
     * @return string
     */
    private function getLoginStatus(string $action): string
    {
        if ($action === 'LOGIN_SUCCESS') {
            return 'success';
        } elseif ($action === 'LOGIN_FAILED' || $action === 'LOGIN_BLOCKED') {
            return 'failed';
        } elseif ($action === 'LOGOUT') {
            return 'logout';
        }
        
        return 'unknown';
    }
    
    /**
     * Get location from IP (placeholder - implement geolocation service)
     *
     * @param string|null $ip
     * @return string|null
     */
    private function getLocationFromIP(?string $ip): ?string
    {
        // TODO: Implement IP geolocation service
        return null;
    }
    
    /**
     * Parse user agent string
     *
     * @param string|null $userAgent
     * @return array
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [];
        }
        
        // Simple user agent parsing
        $device = 'Unknown';
        $browser = 'Unknown';
        $os = 'Unknown';
        
        // Browser detection
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'Edge';
        }
        
        // OS detection
        if (strpos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $os = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false) {
            $os = 'iOS';
        }
        
        // Device detection
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            $device = 'Mobile';
        } else {
            $device = 'Desktop';
        }
        
        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
            'user_agent' => $userAgent,
        ];
    }
    
    /**
     * Format activity description
     *
     * @param array $activity
     * @return string
     */
    private function formatActivityDescription(array $activity): string
    {
        $module = $activity['module'] ?? 'Unknown';
        $action = $activity['action'] ?? 'Unknown';
        
        $descriptions = [
            'AUTHENTICATION' => [
                'LOGIN_SUCCESS' => 'Successfully logged in',
                'LOGIN_FAILED' => 'Failed login attempt',
                'LOGIN_BLOCKED' => 'Login blocked due to too many attempts',
                'LOGOUT' => 'Logged out',
                'REGISTER' => 'User registration',
                'ACTIVATE' => 'Account activated',
                'PASSWORD_RESET' => 'Password reset requested',
            ],
            'USER_MANAGEMENT' => [
                'CREATE' => 'User created',
                'UPDATE' => 'Profile updated',
                'DELETE' => 'User deleted',
                'VIEW' => 'Viewed user profile',
            ],
        ];
        
        return $descriptions[$module][$action] ?? "{$action} in {$module}";
    }
}

