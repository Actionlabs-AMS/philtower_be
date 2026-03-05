<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SecurityMonitorService
{
    /**
     * Monitor for suspicious login patterns
     */
    public function checkSuspiciousLoginPatterns(string $email, string $ip, string $userAgent): array
    {
        $suspicious = false;
        $reasons = [];

        // Check for multiple failed login attempts
        $failedAttempts = Cache::get("failed_logins:{$ip}", 0);
        if ($failedAttempts > 10) {
            $suspicious = true;
            $reasons[] = "Multiple failed login attempts from IP: {$ip}";
        }

        // Check for unusual IP patterns
        $recentLogins = Cache::get("recent_logins:{$email}", []);
        if (count($recentLogins) > 0) {
            $uniqueIPs = array_unique(array_column($recentLogins, 'ip'));
            if (count($uniqueIPs) > 3) {
                $suspicious = true;
                $reasons[] = "Login from multiple IP addresses";
            }
        }

        // Check for unusual user agents
        if ($this->isSuspiciousUserAgent($userAgent)) {
            $suspicious = true;
            $reasons[] = "Suspicious user agent detected";
        }

        // Check for rapid login attempts
        $rapidAttempts = Cache::get("rapid_attempts:{$ip}", 0);
        if ($rapidAttempts > 5) {
            $suspicious = true;
            $reasons[] = "Rapid login attempts detected";
        }

        if ($suspicious) {
            $this->logSecurityEvent('SUSPICIOUS_LOGIN_ATTEMPT', [
                'email' => $this->anonymizeEmail($email),
                'ip' => $ip,
                'user_agent' => $userAgent,
                'reasons' => $reasons,
            ]);

            $this->sendSecurityAlert($email, 'Suspicious Login Attempt', $reasons);
        }

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons,
        ];
    }

    /**
     * Monitor for brute force attacks
     */
    public function checkBruteForceAttack(string $ip, string $endpoint): array
    {
        $key = "brute_force:{$ip}:{$endpoint}";
        $attempts = Cache::get($key, 0);
        
        // Increased threshold to avoid false positives for normal usage
        // Only blocks if more than 100 attempts in 1 hour (for auth endpoints only)
        if ($attempts > 100) {
            $this->logSecurityEvent('BRUTE_FORCE_ATTACK', [
                'ip' => $ip,
                'endpoint' => $endpoint,
                'attempts' => $attempts,
            ]);

            $this->sendSecurityAlert('admin@pathcast.com', 'Brute Force Attack Detected', [
                "IP: {$ip}",
                "Endpoint: {$endpoint}",
                "Attempts: {$attempts}",
            ]);

            return [
                'blocked' => true,
                'attempts' => $attempts,
            ];
        }

        return [
            'blocked' => false,
            'attempts' => $attempts,
        ];
    }

    /**
     * Monitor for unusual API usage patterns
     */
    public function checkUnusualApiUsage(string $userId, string $endpoint, array $data): array
    {
        $suspicious = false;
        $reasons = [];

        // Check for unusual data access patterns
        $recentAccess = Cache::get("api_access:{$userId}", []);
        // Increased threshold to avoid false positives - 500 requests in 24 hours is reasonable
        if (count($recentAccess) > 500) {
            $suspicious = true;
            $reasons[] = "Unusually high API usage";
        }

        // Check for access to sensitive endpoints
        $sensitiveEndpoints = [
            'user-management/users',
            'user-management/roles',
            'system-settings',
        ];

        if (in_array($endpoint, $sensitiveEndpoints)) {
            $sensitiveAccess = Cache::get("sensitive_access:{$userId}", 0);
            // Increased threshold - 200 requests per 24 hours is reasonable for admin users
            if ($sensitiveAccess > 200) {
                $suspicious = true;
                $reasons[] = "Frequent access to sensitive endpoints";
            }
        }

        if ($suspicious) {
            $this->logSecurityEvent('UNUSUAL_API_USAGE', [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'reasons' => $reasons,
            ]);
        }

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons,
        ];
    }

    /**
     * Monitor for data exfiltration attempts
     */
    public function checkDataExfiltration(string $userId, array $data): array
    {
        $suspicious = false;
        $reasons = [];

        // Check for bulk data requests
        if (isset($data['per_page']) && $data['per_page'] > 1000) {
            $suspicious = true;
            $reasons[] = "Large data request detected";
        }

        // Check for unusual export patterns
        $exportCount = Cache::get("export_count:{$userId}", 0);
        if ($exportCount > 10) {
            $suspicious = true;
            $reasons[] = "Frequent data exports";
        }

        if ($suspicious) {
            $this->logSecurityEvent('DATA_EXFILTRATION_ATTEMPT', [
                'user_id' => $userId,
                'reasons' => $reasons,
            ]);
        }

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons,
        ];
    }

    /**
     * Check for suspicious user agent
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python-requests',
            'java/',
            'go-http-client',
        ];

        $userAgent = strtolower($userAgent);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(string $event, array $data): void
    {
        Log::channel('security')->warning('Security Event', [
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ]);
    }

    /**
     * Send security alerts
     */
    private function sendSecurityAlert(string $email, string $subject, array $reasons): void
    {
        try {
            // In a real implementation, you would send an email here
            Log::channel('security')->critical('Security Alert', [
                'email' => $email,
                'subject' => $subject,
                'reasons' => $reasons,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send security alert', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Anonymize email for logging
     */
    private function anonymizeEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            $anonymizedUsername = str_repeat('*', strlen($username));
        } else {
            $anonymizedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $anonymizedUsername . '@' . $domain;
    }

    /**
     * Get security metrics
     */
    public function getSecurityMetrics(): array
    {
        $today = now()->startOfDay();
        
        return [
            'failed_logins_today' => Cache::get('failed_logins_today', 0),
            'suspicious_attempts_today' => Cache::get('suspicious_attempts_today', 0),
            'brute_force_blocks_today' => Cache::get('brute_force_blocks_today', 0),
            'active_sessions' => Cache::get('active_sessions', 0),
            'last_security_scan' => Cache::get('last_security_scan', null),
        ];
    }

    /**
     * Run security scan
     */
    public function runSecurityScan(): array
    {
        $results = [
            'timestamp' => now()->toISOString(),
            'checks' => [],
        ];

        // Check for expired sessions
        $expiredSessions = $this->checkExpiredSessions();
        $results['checks']['expired_sessions'] = $expiredSessions;

        // Check for suspicious IPs
        $suspiciousIPs = $this->checkSuspiciousIPs();
        $results['checks']['suspicious_ips'] = $suspiciousIPs;

        // Check for unusual activity
        $unusualActivity = $this->checkUnusualActivity();
        $results['checks']['unusual_activity'] = $unusualActivity;

        Cache::put('last_security_scan', now()->toISOString(), 3600);

        return $results;
    }

    /**
     * Check for expired sessions
     */
    private function checkExpiredSessions(): array
    {
        // Implementation would check for expired sessions
        return [
            'count' => 0,
            'status' => 'ok',
        ];
    }

    /**
     * Check for suspicious IPs
     */
    private function checkSuspiciousIPs(): array
    {
        // Implementation would check for suspicious IPs
        return [
            'count' => 0,
            'status' => 'ok',
        ];
    }

    /**
     * Check for unusual activity
     */
    private function checkUnusualActivity(): array
    {
        // Implementation would check for unusual activity
        return [
            'count' => 0,
            'status' => 'ok',
        ];
    }
}
