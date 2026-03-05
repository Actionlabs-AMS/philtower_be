<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run backup schedules every minute (if cron is available)
        $schedule->command('backup:run-schedules')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Cleanup expired backups daily at 2 AM
        $schedule->command('backup:cleanup')
            ->dailyAt('02:00')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
