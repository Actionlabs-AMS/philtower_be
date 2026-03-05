<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\AuditTrailHelper;
use Illuminate\Support\Facades\DB;

class AuditTrailMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip logging for certain routes
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Log the API request
        $this->logApiRequest($request, $response, $executionTime);

        // Log database queries if enabled (disabled by default to prevent spam)
        if (config('app.debug') && config('audit.log_queries', false)) {
            $this->logQueries();
        }

        return $response;
    }

    /**
     * Determine if logging should be skipped for this request
     *
     * @param Request $request
     * @return bool
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $skipRoutes = [
            'api/files/url', // File URL generation
            'api/options/*', // Options endpoints
            'api/dashboard/stats', // Dashboard stats (frequent calls)
        ];

        $currentRoute = $request->route()?->getName() ?? $request->path();

        foreach ($skipRoutes as $skipRoute) {
            if (fnmatch($skipRoute, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the API request
     *
     * @param Request $request
     * @param $response
     * @param float $executionTime
     * @return void
     */
    private function logApiRequest(Request $request, $response, float $executionTime): void
    {
        try {
            $module = $this->extractModule($request);
            $action = $this->extractAction($request);
            
            // Prepare request data (exclude sensitive information)
            $requestData = $this->sanitizeRequestData($request->all());
            
            // Prepare response data
            $responseData = $this->prepareResponseData($response);
            
            // Log the API request with execution details
            AuditTrailHelper::log(
                $module,
                $action,
                [
                    'execution_time' => $executionTime . 'ms',
                    'request_size' => strlen(json_encode($requestData)),
                    'response_size' => strlen(json_encode($responseData)),
                    'status_code' => $response->getStatusCode(),
                    'request_data' => $requestData,
                    'response_data' => $responseData
                ],
                $this->extractResourceId($request),
                null,
                $requestData
            );
        } catch (\Exception $e) {
            \Log::error('Audit Trail Middleware Error: ' . $e->getMessage());
        }
    }

    /**
     * Log database queries
     *
     * @return void
     */
    private function logQueries(): void
    {
        try {
            $queries = DB::getQueryLog();
            
            foreach ($queries as $query) {
                AuditTrailHelper::logQuery(
                    $query['query'],
                    $query['bindings'],
                    $query['time']
                );
            }
        } catch (\Exception $e) {
            \Log::error('Query Logging Error: ' . $e->getMessage());
        }
    }

    /**
     * Extract module name from request
     *
     * @param Request $request
     * @return string
     */
    private function extractModule(Request $request): string
    {
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Remove 'api' prefix if present
        if (isset($segments[0]) && $segments[0] === 'api') {
            array_shift($segments);
        }
        
        // Get the first segment as module
        $module = $segments[0] ?? 'UNKNOWN';
        
        // Map common modules to readable names
        $moduleMap = [
            'user-management' => 'USER_MANAGEMENT',
            'content-management' => 'CONTENT_MANAGEMENT',
            'site-management' => 'SITE_MANAGEMENT',
            'system-settings' => 'SYSTEM_SETTINGS',
            'dashboard' => 'DASHBOARD',
            'files' => 'FILE_MANAGEMENT',
            'options' => 'OPTIONS',
        ];
        
        return $moduleMap[$module] ?? strtoupper($module);
    }

    /**
     * Extract action from request
     *
     * @param Request $request
     * @return string
     */
    private function extractAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();
        
        // Map HTTP methods to actions
        $actionMap = [
            'GET' => 'VIEW',
            'POST' => 'CREATE',
            'PUT' => 'UPDATE',
            'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
        ];
        
        $baseAction = $actionMap[$method] ?? 'UNKNOWN';
        
        // Check for specific actions in the path
        if (str_contains($path, 'bulk/delete')) {
            return 'BULK_DELETE';
        } elseif (str_contains($path, 'bulk/restore')) {
            return 'BULK_RESTORE';
        } elseif (str_contains($path, 'import')) {
            return 'IMPORT';
        } elseif (str_contains($path, 'export')) {
            return 'EXPORT';
        } elseif (str_contains($path, 'validate')) {
            return 'VALIDATE';
        } elseif (str_contains($path, 'decline')) {
            return 'DECLINE';
        } elseif (str_contains($path, 'confirm')) {
            return 'CONFIRM';
        }
        
        return $baseAction;
    }

    /**
     * Extract resource ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function extractResourceId(Request $request): ?string
    {
        $route = $request->route();
        
        if ($route && isset($route->parameters['id'])) {
            return (string) $route->parameters['id'];
        }
        
        return null;
    }

    /**
     * Sanitize request data to remove sensitive information
     *
     * @param array $data
     * @return array
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'user_pass',
            'user_salt',
            'user_activation_key',
            'remember_token',
            'api_token',
            'access_token',
            'refresh_token',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        // Recursively sanitize nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }
        
        return $data;
    }

    /**
     * Prepare response data for logging
     *
     * @param $response
     * @return array
     */
    private function prepareResponseData($response): array
    {
        try {
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Limit response data size
                if (strlen($content) > 10000) {
                    return [
                        'message' => 'Response too large to log',
                        'size' => strlen($content),
                        'status_code' => $response->getStatusCode(),
                    ];
                }
                
                return $data;
            }
            
            return [
                'content_type' => $response->headers->get('Content-Type'),
                'size' => strlen($content),
                'status_code' => $response->getStatusCode(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to parse response data',
                'status_code' => $response->getStatusCode(),
            ];
        }
    }
}
