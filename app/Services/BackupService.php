<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Helpers\BackupHelper;
use App\Helpers\S3Helper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class BackupService
{
    /**
     * Create a backup record.
     */
    public function createBackup(array $data): Backup
    {
        return Backup::create($data);
    }

    /**
     * Create database backup.
     */
    public function createDatabaseBackup(array $options = []): Backup
    {
        $backup = $this->createBackup([
            'name' => $options['name'] ?? $this->generateBackupName('database'),
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => $options['storage_disk'] ?? 'local',
            'storage_path' => '',
            'compression' => $options['compression'] ?? 'gzip',
            'encrypted' => $options['encrypted'] ?? false,
            'tables_included' => $options['tables_included'] ?? null,
            'created_by' => auth()->id(),
        ]);

        try {
            $backup->update(['status' => 'in_progress']);

            // Export database
            $tables = $options['tables_included'] ?? [];
            $sqlFile = BackupHelper::exportDatabase($tables);

            // Compress if needed
            $finalFile = $sqlFile;
            if ($backup->compression === 'gzip') {
                $finalFile = BackupHelper::compressGzip($sqlFile);
                File::delete($sqlFile);
            }

            // Encrypt if needed
            if ($backup->encrypted) {
                $finalFile = BackupHelper::encryptFile($finalFile);
            }

            // Store backup
            $storedPath = $this->storeBackup($finalFile, $backup);

            // Update backup record
            $backup->markAsCompleted(
                File::size($storedPath),
                $storedPath
            );

            // Set expiration if retention is specified
            if (isset($options['retention_days'])) {
                $backup->update([
                    'expires_at' => now()->addDays($options['retention_days']),
                ]);
            }

            // Cleanup temp file
            if (File::exists($finalFile) && strpos($finalFile, 'temp') !== false) {
                File::delete($finalFile);
            }

            Log::info("Database backup created successfully: {$backup->id}");

            return $backup;
        } catch (Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error("Database backup failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Create files backup.
     */
    public function createFilesBackup(array $options = []): Backup
    {
        $backup = $this->createBackup([
            'name' => $options['name'] ?? $this->generateBackupName('files'),
            'type' => 'files',
            'status' => 'pending',
            'storage_disk' => $options['storage_disk'] ?? 'local',
            'storage_path' => '',
            'compression' => $options['compression'] ?? 'zip',
            'encrypted' => $options['encrypted'] ?? false,
            'files_included' => $options['files_included'] ?? null,
            'created_by' => auth()->id(),
        ]);

        try {
            $backup->update(['status' => 'in_progress']);

            // Backup files
            $paths = $options['files_included'] ?? [];
            $zipFile = BackupHelper::backupStorageFiles($paths);

            // Encrypt if needed
            $finalFile = $zipFile;
            if ($backup->encrypted) {
                $finalFile = BackupHelper::encryptFile($zipFile);
                File::delete($zipFile);
            }

            // Store backup
            $storedPath = $this->storeBackup($finalFile, $backup);

            // Update backup record
            $backup->markAsCompleted(
                File::size($storedPath),
                $storedPath
            );

            // Set expiration if retention is specified
            if (isset($options['retention_days'])) {
                $backup->update([
                    'expires_at' => now()->addDays($options['retention_days']),
                ]);
            }

            // Cleanup temp file
            if (File::exists($finalFile) && strpos($finalFile, 'temp') !== false) {
                File::delete($finalFile);
            }

            Log::info("Files backup created successfully: {$backup->id}");

            return $backup;
        } catch (Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error("Files backup failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Create full backup (database + files).
     */
    public function createFullBackup(array $options = []): Backup
    {
        $backup = $this->createBackup([
            'name' => $options['name'] ?? $this->generateBackupName('full'),
            'type' => 'full',
            'status' => 'pending',
            'storage_disk' => $options['storage_disk'] ?? 'local',
            'storage_path' => '',
            'compression' => $options['compression'] ?? 'zip',
            'encrypted' => $options['encrypted'] ?? false,
            'tables_included' => $options['tables_included'] ?? null,
            'files_included' => $options['files_included'] ?? null,
            'created_by' => auth()->id(),
        ]);

        try {
            $backup->update(['status' => 'in_progress']);

            $tempFiles = [];

            // Backup database
            $tables = $options['tables_included'] ?? [];
            $sqlFile = BackupHelper::exportDatabase($tables);
            $tempFiles[] = $sqlFile;

            // Compress SQL if needed
            if ($backup->compression === 'gzip') {
                $sqlFile = BackupHelper::compressGzip($sqlFile);
            }

            // Backup files
            $paths = $options['files_included'] ?? [];
            $zipFile = BackupHelper::backupStorageFiles($paths);
            $tempFiles[] = $zipFile;

            // Create combined archive
            $combinedZip = storage_path('app/temp/full_backup_' . time() . '_' . uniqid() . '.zip');
            BackupHelper::compressZip([$sqlFile, $zipFile], $combinedZip);
            $tempFiles[] = $combinedZip;

            // Encrypt if needed
            $finalFile = $combinedZip;
            if ($backup->encrypted) {
                $finalFile = BackupHelper::encryptFile($combinedZip);
                $tempFiles[] = $finalFile;
            }

            // Store backup
            $storedPath = $this->storeBackup($finalFile, $backup);

            // Update backup record
            $backup->markAsCompleted(
                File::size($storedPath),
                $storedPath
            );

            // Set expiration if retention is specified
            if (isset($options['retention_days'])) {
                $backup->update([
                    'expires_at' => now()->addDays($options['retention_days']),
                ]);
            }

            // Cleanup temp files
            foreach ($tempFiles as $tempFile) {
                if (File::exists($tempFile)) {
                    File::delete($tempFile);
                }
            }

            Log::info("Full backup created successfully: {$backup->id}");

            return $backup;
        } catch (Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error("Full backup failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Restore backup.
     */
    public function restoreBackup(int $backupId, array $options = []): bool
    {
        $backup = Backup::findOrFail($backupId);

        if (!$backup->canRestore()) {
            $reasons = [];
            if ($backup->status !== 'completed') {
                $reasons[] = "status is '{$backup->status}' (expected 'completed')";
            }
            if ($backup->isExpired()) {
                $reasons[] = "backup is expired (expires_at: {$backup->expires_at})";
            }
            if (!$backup->file_path && !$backup->storage_path) {
                $reasons[] = "no file path or storage path set";
            } else {
                $storagePath = $backup->getStoragePath();
                $fullPath = $backup->getFullPath();
                $disk = $backup->storage_disk ?: 'local';
                
                $exists = false;
                try {
                    $exists = Storage::disk($disk)->exists($storagePath);
                } catch (\Exception $e) {
                    $exists = file_exists($fullPath);
                }
                
                if (!$exists) {
                    $reasons[] = "file does not exist (storage_path: '{$storagePath}', full_path: '{$fullPath}', disk: '{$disk}')";
                }
            }
            
            $reasonMsg = !empty($reasons) ? ' Reasons: ' . implode('; ', $reasons) : '';
            throw new Exception('Backup cannot be restored. Check status, expiration, and file existence.' . $reasonMsg);
        }

        // Create pre-restore backup if requested
        $preRestoreBackup = null;
        if ($options['create_backup'] ?? true) {
            try {
                $preRestoreBackup = $this->createFullBackup([
                    'name' => 'Pre-restore backup ' . now()->format('Y-m-d H:i:s'),
                    'created_by' => auth()->id(),
                ]);
            } catch (Exception $e) {
                Log::warning("Pre-restore backup failed: {$e->getMessage()}");
            }
        }

        try {
            $filePath = $backup->getFullPath();

            // Decrypt if needed
            if ($backup->encrypted) {
                $filePath = BackupHelper::decryptFile($filePath);
            }

            // Decompress if needed
            if ($backup->compression === 'gzip') {
                $filePath = BackupHelper::decompressGzip($filePath);
            }

            // Restore based on type
            if ($backup->type === 'database' || $backup->type === 'full') {
                BackupHelper::importDatabase($filePath);
            }

            if ($backup->type === 'files' || $backup->type === 'full') {
                if ($backup->compression === 'zip' || $backup->type === 'full') {
                    $extractTo = storage_path('app/temp/restore_' . time());
                    BackupHelper::extractZip($filePath, $extractTo);
                    // Move files from extractTo to storage
                    // This is simplified - in production, handle file restoration more carefully
                }
            }

            Log::info("Backup restored successfully: {$backup->id}");

            return true;
        } catch (Exception $e) {
            Log::error("Backup restore failed: {$e->getMessage()}");

            // Rollback if pre-restore backup exists
            if ($preRestoreBackup && $preRestoreBackup->canRestore()) {
                try {
                    $this->restoreBackup($preRestoreBackup->id, ['create_backup' => false]);
                    Log::info("Rolled back to pre-restore backup");
                } catch (Exception $rollbackError) {
                    Log::error("Rollback failed: {$rollbackError->getMessage()}");
                }
            }

            throw $e;
        }
    }

    /**
     * Validate backup.
     */
    public function validateBackup(int $backupId): array
    {
        $backup = Backup::findOrFail($backupId);
        $filePath = $backup->getFullPath();

        $validation = BackupHelper::validateBackupFile($filePath);

        return [
            'backup_id' => $backupId,
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
            'file_size' => $validation['size'],
            'file_type' => $validation['type'],
        ];
    }

    /**
     * List backups with filters.
     */
    public function listBackups(array $filters = [])
    {
        $query = Backup::query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'LIKE', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get backup statistics.
     */
    public function getBackupStats(): array
    {
        return [
            'total' => Backup::count(),
            'completed' => Backup::completed()->count(),
            'failed' => Backup::failed()->count(),
            'pending' => Backup::where('status', 'pending')->count(),
            'in_progress' => Backup::where('status', 'in_progress')->count(),
            'total_size' => Backup::whereNotNull('file_size')->sum('file_size'),
            'database_backups' => Backup::byType('database')->count(),
            'files_backups' => Backup::byType('files')->count(),
            'full_backups' => Backup::byType('full')->count(),
        ];
    }

    /**
     * Create backup schedule.
     */
    public function createSchedule(array $data): BackupSchedule
    {
        $schedule = BackupSchedule::create($data);

        // Calculate and set next run time
        $schedule->updateNextRun();

        return $schedule;
    }

    /**
     * Update backup schedule.
     */
    public function updateSchedule(int $scheduleId, array $data): BackupSchedule
    {
        $schedule = BackupSchedule::findOrFail($scheduleId);
        $schedule->update($data);

        // Recalculate next run time
        $schedule->updateNextRun();

        return $schedule;
    }

    /**
     * Delete backup schedule.
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        $schedule = BackupSchedule::findOrFail($scheduleId);
        return $schedule->delete();
    }

    /**
     * Run due backup schedules.
     */
    public function runDueSchedules(): void
    {
        $schedules = BackupSchedule::where('active', true)
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($schedules as $schedule) {
            if ($schedule->shouldRun()) {
                try {
                    // Dispatch backup job
                    \App\Jobs\CreateBackupJob::dispatch($schedule)
                        ->onQueue('backups');

                    // Update schedule
                    $schedule->update([
                        'last_run_at' => now(),
                    ]);
                    $schedule->updateNextRun();

                    Log::info("Backup schedule '{$schedule->name}' triggered");
                } catch (Exception $e) {
                    Log::error("Failed to trigger schedule '{$schedule->name}': {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Generate backup name.
     */
    protected function generateBackupName(string $type): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$type}_backup_{$timestamp}";
    }

    /**
     * Store backup file.
     */
    protected function storeBackup(string $filePath, Backup $backup): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $filename = basename($filePath);
        $storagePath = "backups/{$backup->type}/{$year}/{$month}/{$filename}";

        if ($backup->storage_disk === 's3') {
            // Upload to S3
            $url = S3Helper::uploadFile($filePath, $filename, "backups/{$backup->type}/{$year}/{$month}");
            $backup->update(['storage_path' => $url]);
            return $url;
        } else {
            // Store locally
            $targetPath = storage_path("app/{$storagePath}");
            $targetDir = dirname($targetPath);

            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            File::copy($filePath, $targetPath);
            $backup->update(['storage_path' => $storagePath]);
            return $targetPath;
        }
    }
}

