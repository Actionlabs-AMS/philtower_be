<?php

namespace App\Jobs;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $schedule;

    /**
     * Create a new job instance.
     */
    public function __construct(BackupSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            $options = [
                'name' => $this->schedule->name . ' - ' . now()->format('Y-m-d H:i:s'),
                'storage_disk' => $this->schedule->storage_disk,
                'compression' => $this->schedule->compression,
                'encrypted' => $this->schedule->encrypted,
                'tables_included' => $this->schedule->tables_included,
                'files_included' => $this->schedule->files_included,
                'retention_days' => $this->schedule->retention_days,
            ];

            switch ($this->schedule->type) {
                case 'database':
                    $backupService->createDatabaseBackup($options);
                    break;
                case 'files':
                    $backupService->createFilesBackup($options);
                    break;
                case 'full':
                    $backupService->createFullBackup($options);
                    break;
            }

            Log::info("Scheduled backup completed for schedule: {$this->schedule->name}");
        } catch (\Exception $e) {
            Log::error("Scheduled backup failed for schedule {$this->schedule->name}: {$e->getMessage()}");
            throw $e;
        }
    }
}

