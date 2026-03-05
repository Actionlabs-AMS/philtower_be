<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SecurityMonitorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Security Dashboard",
 *     description="API endpoints for security monitoring and dashboard"
 * )
 */
class SecurityDashboardController extends Controller
{
    protected $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
    }

    /**
     * Get security metrics
     */
    public function getMetrics(): JsonResponse
    {
        try {
            $metrics = $this->securityMonitor->getSecurityMetrics();
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get security metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve security metrics',
            ], 500);
        }
    }

    /**
     * Run security scan
     */
    public function runSecurityScan(): JsonResponse
    {
        try {
            $results = $this->securityMonitor->runSecurityScan();
            
            return response()->json([
                'success' => true,
                'scan_results' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to run security scan', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to run security scan',
            ], 500);
        }
    }

    /**
     * Get security events
     */
    public function getSecurityEvents(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $offset = $request->get('offset', 0);
            
            // In a real implementation, you would query a security events table
            $events = [
                [
                    'id' => 1,
                    'event_type' => 'SUSPICIOUS_LOGIN_ATTEMPT',
                    'description' => 'Multiple failed login attempts detected',
                    'ip_address' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0...',
                    'timestamp' => now()->subMinutes(30)->toISOString(),
                    'severity' => 'high',
                ],
                [
                    'id' => 2,
                    'event_type' => 'BRUTE_FORCE_ATTACK',
                    'description' => 'Brute force attack blocked',
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'curl/7.68.0',
                    'timestamp' => now()->subHours(2)->toISOString(),
                    'severity' => 'critical',
                ],
            ];

            return response()->json([
                'success' => true,
                'events' => $events,
                'total' => count($events),
                'limit' => $limit,
                'offset' => $offset,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get security events', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve security events',
            ], 500);
        }
    }

    /**
     * Get blocked IPs
     */
    public function getBlockedIPs(): JsonResponse
    {
        try {
            // In a real implementation, you would query a blocked IPs table
            $blockedIPs = [
                [
                    'ip' => '192.168.1.100',
                    'reason' => 'Brute force attack',
                    'blocked_at' => now()->subHours(1)->toISOString(),
                    'expires_at' => now()->addHours(23)->toISOString(),
                ],
                [
                    'ip' => '10.0.0.1',
                    'reason' => 'Suspicious activity',
                    'blocked_at' => now()->subMinutes(30)->toISOString(),
                    'expires_at' => now()->addHours(1)->toISOString(),
                ],
            ];

            return response()->json([
                'success' => true,
                'blocked_ips' => $blockedIPs,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get blocked IPs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve blocked IPs',
            ], 500);
        }
    }

    /**
     * Unblock an IP address
     */
    public function unblockIP(Request $request): JsonResponse
    {
        try {
            $ip = $request->input('ip');
            
            if (!$ip) {
                return response()->json([
                    'success' => false,
                    'message' => 'IP address is required',
                ], 400);
            }

            // In a real implementation, you would remove the IP from the blocked list
            Cache::forget("blocked_ip:{$ip}");
            
            Log::info('IP address unblocked', [
                'ip' => $ip,
                'unblocked_by' => $request->user()->id ?? 'system',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IP address unblocked successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to unblock IP', [
                'ip' => $request->input('ip'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock IP address',
            ], 500);
        }
    }

    /**
     * Get security configuration
     */
    public function getSecurityConfig(): JsonResponse
    {
        try {
            $config = [
                'rate_limiting' => [
                    'api_limit' => config('security.rate_limiting.api_limit', 60),
                    'auth_limit' => config('security.rate_limiting.auth_limit', 5),
                    'login_limit' => config('security.rate_limiting.login_limit', 3),
                ],
                'security_headers' => [
                    'csp_enabled' => config('security.headers.csp_enabled', true),
                    'hsts_enabled' => config('security.headers.hsts_enabled', true),
                    'x_frame_options' => config('security.headers.x_frame_options', 'DENY'),
                ],
                'audit_trail' => [
                    'enabled' => config('audit.enabled', true),
                    'retention_days' => config('audit.retention_days', 90),
                    'log_level' => config('audit.log_level', 'info'),
                ],
                'two_factor_auth' => [
                    'enabled' => true,
                    'email_verification' => true,
                    'backup_codes' => true,
                ],
            ];

            return response()->json([
                'success' => true,
                'config' => $config,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get security configuration', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve security configuration',
            ], 500);
        }
    }

    /**
     * Update security configuration
     */
    public function updateSecurityConfig(Request $request): JsonResponse
    {
        try {
            $config = $request->input('config');
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration data is required',
                ], 400);
            }

            // In a real implementation, you would update the configuration
            // For now, we'll just log the changes
            Log::info('Security configuration updated', [
                'updated_by' => $request->user()->id ?? 'system',
                'changes' => $config,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Security configuration updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update security configuration', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update security configuration',
            ], 500);
        }
    }
}
