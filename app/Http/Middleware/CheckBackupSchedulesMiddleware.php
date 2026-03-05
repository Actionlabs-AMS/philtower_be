<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BackupService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckBackupSchedulesMiddleware
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Handle an incoming request and check for due backup schedules.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check on authenticated requests to reduce overhead
        if (auth()->check()) {
            // Check cooldown to prevent excessive checks
            $cooldown = config('backup.scheduling.check_cooldown', 5); // minutes
            $lastCheck = Cache::get('backup_schedule_last_check');
            
            if (!$lastCheck || now()->diffInMinutes($lastCheck) >= $cooldown) {
                // Set cache to prevent concurrent checks
                Cache::put('backup_schedule_last_check', now(), now()->addMinutes($cooldown + 1));
                
                // Check schedules asynchronously (non-blocking)
                dispatch(function () {
                    try {
                        $this->backupService->runDueSchedules();
                    } catch (\Exception $e) {
                        \Log::error('Backup schedule check failed: ' . $e->getMessage());
                    }
                })->afterResponse(); // Run after response is sent
            }
        }

        return $next($request);
    }
}

