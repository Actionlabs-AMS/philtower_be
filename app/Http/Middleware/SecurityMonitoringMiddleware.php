<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SecurityMonitorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SecurityMonitoringMiddleware
{
    protected $securityMonitor;

    public function __construct(SecurityMonitorService $securityMonitor)
    {
        $this->securityMonitor = $securityMonitor;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $endpoint = $request->path();
        $userId = $request->user()?->id;

        // Check for brute force attacks (only for auth endpoints, not regular API calls)
        // Skip brute force checking for authenticated API requests to avoid false positives
        if ($userId && $request->is('api/*')) {
            // Authenticated API requests bypass brute force checks
            // Only check for unauthenticated auth endpoints
        } elseif (str_contains($endpoint, 'login') || str_contains($endpoint, 'auth') || str_contains($endpoint, 'signup') || str_contains($endpoint, '2fa')) {
            $bruteForceCheck = $this->securityMonitor->checkBruteForceAttack($ip, $endpoint);
            if ($bruteForceCheck['blocked']) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'error' => 'BRUTE_FORCE_BLOCKED',
                ], 429);
            }

            // Increment brute force counter only for auth endpoints (unauthenticated)
            $key = "brute_force:{$ip}:{$endpoint}";
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, 3600); // 1 hour
        }

        // Check for suspicious login patterns (for auth endpoints)
        if (str_contains($endpoint, 'login') || str_contains($endpoint, 'auth')) {
            $email = $request->input('email');
            if ($email) {
                $suspiciousCheck = $this->securityMonitor->checkSuspiciousLoginPatterns($email, $ip, $userAgent);
                if ($suspiciousCheck['suspicious']) {
                    // Log the suspicious activity but don't block the request
                    // The actual blocking should be handled by rate limiting
                }
            }
        }

        // Check for unusual API usage (for authenticated users)
        if ($userId && $request->is('api/*')) {
            $unusualUsageCheck = $this->securityMonitor->checkUnusualApiUsage(
                $userId,
                $endpoint,
                $request->all()
            );

            if ($unusualUsageCheck['suspicious']) {
                // Log the unusual usage
                // Consider implementing additional restrictions here
            }
        }

        // Check for data exfiltration attempts
        if ($userId && $request->is('api/*')) {
            $exfiltrationCheck = $this->securityMonitor->checkDataExfiltration(
                $userId,
                $request->all()
            );

            if ($exfiltrationCheck['suspicious']) {
                // Log the potential exfiltration attempt
                // Consider implementing additional restrictions here
            }
        }

        // Track API access for monitoring
        if ($userId) {
            $this->trackApiAccess($userId, $endpoint, $ip);
        }

        $response = $next($request);

        // Track response for security analysis
        $this->trackResponse($request, $response);

        return $response;
    }

    /**
     * Track API access for monitoring
     */
    private function trackApiAccess(string $userId, string $endpoint, string $ip): void
    {
        $key = "api_access:{$userId}";
        $access = Cache::get($key, []);
        
        $access[] = [
            'endpoint' => $endpoint,
            'ip' => $ip,
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 100 accesses
        if (count($access) > 100) {
            $access = array_slice($access, -100);
        }

        Cache::put($key, $access, 86400); // 24 hours
    }

    /**
     * Track response for security analysis
     */
    private function trackResponse(Request $request, $response): void
    {
        $statusCode = $response->getStatusCode();
        
        // Track error responses
        if ($statusCode >= 400) {
            $key = "error_responses:{$request->ip()}";
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, 3600); // 1 hour
        }

        // Track successful responses for pattern analysis
        if ($statusCode >= 200 && $statusCode < 300) {
            $key = "success_responses:{$request->ip()}";
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, 3600); // 1 hour
        }
    }
}
