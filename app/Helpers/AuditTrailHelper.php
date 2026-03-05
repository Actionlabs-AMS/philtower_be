<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditTrailHelper
{
    /**
     * Log an action to the audit trail
     *
     * @param string $module
     * @param string $action
     * @param array $data
     * @param string|null $resourceId
     * @param array|null $oldData
     * @param array|null $newData
     * @return bool
     */
    public static function log(
        string $module,
        string $action,
        array $data = [],
        ?string $resourceId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): bool {
        try {
            // Allow user_id to be passed in data array (useful for failed logins where user isn't authenticated)
            $userId = $data['user_id'] ?? auth()->id();
            $userEmail = $data['user_email'] ?? auth()->user()?->user_email;
            
            // Remove user_id and user_email from data if they were passed (to avoid duplication)
            $cleanData = $data;
            unset($cleanData['user_id'], $cleanData['user_email']);
            
            $logData = [
                'timestamp' => now()->toISOString(),
                'module' => $module,
                'action' => $action,
                'resource_id' => $resourceId,
                'data' => $cleanData,
                'old_data' => $oldData,
                'new_data' => $newData,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            // Log to Laravel log file
            try {
                Log::channel('audit')->info('Audit Trail', $logData);
                
                // Also log to main log for debugging
                if ($module === 'AUTHENTICATION' && in_array($action, ['LOGOUT', 'LOGIN_FAILED', 'LOGIN_BLOCKED'])) {
                    Log::info('AuditTrailHelper: Logging authentication action', [
                        'module' => $module,
                        'action' => $action,
                        'user_id' => $logData['user_id'],
                        'timestamp' => $logData['timestamp'],
                    ]);
                }
            } catch (\Exception $logException) {
                Log::error('Audit Trail Log Channel Error', [
                    'error' => $logException->getMessage(),
                    'module' => $module,
                    'action' => $action,
                    'trace' => $logException->getTraceAsString(),
                ]);
                throw $logException;
            }

            // Store in database if audit table exists
            if (self::auditTableExists()) {
                self::storeInDatabase($logData);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Audit Trail Logging Error', [
                'error' => $e->getMessage(),
                'module' => $module,
                'action' => $action,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Log database queries
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return bool
     */
    public static function logQuery(string $query, array $bindings, float $time): bool
    {
        try {
            $logData = [
                'timestamp' => now()->toISOString(),
                'type' => 'QUERY',
                'query' => $query,
                'bindings' => $bindings,
                'execution_time' => $time . 'ms',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
            ];

            Log::channel('audit')->info('Database Query', $logData);
            return true;
        } catch (\Exception $e) {
            Log::error('Query Logging Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if audit table exists (always false for file-based logging)
     *
     * @return bool
     */
    private static function auditTableExists(): bool
    {
        return false; // Using file-based logging instead of database
    }

    /**
     * Store audit log in database (disabled for file-based logging)
     *
     * @param array $logData
     * @return void
     */
    private static function storeInDatabase(array $logData): void
    {
        // File-based logging is handled by Log::channel('audit')
        // No database storage needed
    }

    /**
     * Get audit logs for a specific resource
     *
     * @param string $resourceId
     * @param string|null $module
     * @return \Illuminate\Support\Collection
     */
    public static function getResourceLogs(string $resourceId, ?string $module = null)
    {
        try {
            $query = DB::table('audit_trails')
                ->where('resource_id', $resourceId)
                ->orderBy('created_at', 'desc');

            if ($module) {
                $query->where('module', $module);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Get Resource Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get audit logs for a specific user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getUserLogs(int $userId, int $limit = 100)
    {
        try {
            return DB::table('audit_trails')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Get User Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get audit logs for a specific module
     *
     * @param string $module
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getModuleLogs(string $module, int $limit = 100)
    {
        try {
            return DB::table('audit_trails')
                ->where('module', $module)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Get Module Logs Error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Clean up old audit logs
     *
     * @param int $daysToKeep
     * @return int Number of records deleted
     */
    public static function cleanupOldLogs(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);
            
            $deletedCount = DB::table('audit_trails')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            Log::info("Cleaned up {$deletedCount} old audit log records");
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Cleanup Old Logs Error: ' . $e->getMessage());
            return 0;
        }
    }
}
