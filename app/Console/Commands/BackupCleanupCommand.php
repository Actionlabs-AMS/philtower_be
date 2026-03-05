<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Backup;
use Illuminate\Support\Facades\File;

class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired backups...');

        $expiredBackups = Backup::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($expiredBackups as $backup) {
            try {
                // Delete file if exists
                if ($backup->file_path) {
                    if ($backup->storage_disk === 'local') {
                        $fullPath = $backup->getFullPath();
                        if (File::exists($fullPath)) {
                            File::delete($fullPath);
                        }
                    }
                    // S3 cleanup can be handled separately if needed
                }

                // Delete backup record
                $backup->forceDelete();
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to delete backup {$backup->id}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->info("Cleanup completed. Deleted: {$deletedCount}, Failed: {$failedCount}");

        return Command::SUCCESS;
    }
}

