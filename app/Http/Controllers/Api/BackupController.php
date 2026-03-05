<?php

namespace App\Http\Controllers\Api;

use App\Services\BackupService;
use App\Services\MessageService;
use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Helpers\BackupHelper;
use App\Http\Requests\StoreBackupRequest;
use App\Http\Requests\RestoreBackupRequest;
use App\Http\Requests\CreateBackupScheduleRequest;
use App\Http\Requests\UpdateBackupScheduleRequest;
use App\Http\Requests\WebhookTriggerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends BaseController
{
    protected $backupService;

    public function __construct(BackupService $backupService, MessageService $messageService)
    {
        // Call the parent constructor to initialize services
        parent::__construct($backupService, $messageService);
        $this->backupService = $backupService;
    }

    /**
     * List backups.
     */
    public function index()
    {
        try {
            $request = request();
            $filters = [
                'type' => $request->get('type'),
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'per_page' => $request->get('per_page', 15),
            ];

            $backups = $this->backupService->listBackups($filters);

            return response()->json($backups);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to list backups: ' . $e->getMessage());
        }
    }

    /**
     * Create backup.
     */
    public function store(StoreBackupRequest $request): JsonResponse
    {
        try {
            $options = $request->only([
                'name', 'compression', 'encrypted', 'storage_disk',
                'tables_included', 'files_included', 'retention_days'
            ]);

            switch ($request->type) {
                case 'database':
                    $backup = $this->backupService->createDatabaseBackup($options);
                    break;
                case 'files':
                    $backup = $this->backupService->createFilesBackup($options);
                    break;
                case 'full':
                    $backup = $this->backupService->createFullBackup($options);
                    break;
                default:
                    return response()->json(['error' => 'Invalid backup type'], 400);
            }

            return response()->json([
                'message' => 'Backup created successfully',
                'data' => $backup,
            ], 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Backup creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get backup details.
     */
    public function show($id)
    {
        try {
            $id = (int) $id;
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid backup ID'], 404);
            }

            $backup = Backup::findOrFail($id);

            return response()->json($backup);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to get backup: ' . $e->getMessage());
        }
    }

    /**
     * Delete backup.
     */
    public function destroy($id)
    {
        try {
            $id = (int) $id;
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid backup ID'], 404);
            }

            $backup = Backup::findOrFail($id);

            // Delete file if exists
            if ($backup->file_path) {
                try {
                    if ($backup->storage_disk === 's3') {
                        // Delete from S3 if needed
                    } else {
                        $fullPath = $backup->getFullPath();
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete backup file: {$e->getMessage()}");
                }
            }

            $backup->delete();

            return response()->json(['message' => 'Backup deleted successfully']);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Download backup.
     */
    public function download($id): StreamedResponse
    {
        $id = (int) $id;
        if ($id <= 0) {
            abort(404, 'Invalid backup ID');
        }

        $backup = Backup::findOrFail($id);

        if (!$backup->canRestore()) {
            abort(404, 'Backup file not found or expired');
        }

        $disk = $backup->storage_disk ?: 'local';
        $storagePath = $backup->getStoragePath();

        // Get file extension from the stored file
        $extension = pathinfo($storagePath, PATHINFO_EXTENSION);
        if (!$extension) {
            // Try to get extension from file_path or storage_path
            $fullPath = $backup->getFullPath();
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'backup';
        }

        $downloadName = $backup->name . '.' . $extension;

        return Storage::disk($disk)->download($storagePath, $downloadName);
    }

    /**
     * Restore backup.
     */
    public function restoreBackup(RestoreBackupRequest $request, $id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid backup ID'], 404);
        }

        try {
            $options = [
                'create_backup' => $request->get('create_backup', true),
            ];

            $this->backupService->restoreBackup($id, $options);

            return response()->json(['message' => 'Backup restored successfully']);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate backup.
     */
    public function validateBackup($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid backup ID'], 404);
        }

        try {
            $validation = $this->backupService->validateBackup($id);

            return response()->json($validation);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get backup statistics.
     */
    public function stats()
    {
        try {
            $stats = $this->backupService->getBackupStats();

            return response()->json($stats);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to get backup statistics: ' . $e->getMessage());
        }
    }

    /**
     * List schedules.
     */
    public function schedules(): JsonResponse
    {
        $schedules = BackupSchedule::orderBy('created_at', 'desc')->get();

        return response()->json($schedules);
    }

    /**
     * Create schedule.
     */
    public function createSchedule(CreateBackupScheduleRequest $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['created_by'] = auth()->id();

            $schedule = $this->backupService->createSchedule($data);

            return response()->json([
                'message' => 'Schedule created successfully',
                'data' => $schedule,
            ], 201);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Schedule creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get schedule details.
     */
    public function getSchedule($id): JsonResponse
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid schedule ID'], 404);
        }

        $schedule = BackupSchedule::findOrFail($id);

        return response()->json($schedule);
    }

    /**
     * Update schedule.
     */
    public function updateSchedule(UpdateBackupScheduleRequest $request, $id): JsonResponse
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid schedule ID'], 404);
        }

        try {
            $schedule = $this->backupService->updateSchedule($id, $request->all());

            return response()->json([
                'message' => 'Schedule updated successfully',
                'data' => $schedule,
            ]);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Schedule update failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete schedule.
     */
    public function deleteSchedule($id): JsonResponse
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid schedule ID'], 404);
        }

        $this->backupService->deleteSchedule($id);

        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    /**
     * Run schedule manually.
     */
    public function runSchedule($id): JsonResponse
    {
        $id = (int) $id;
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid schedule ID'], 404);
        }

        $schedule = BackupSchedule::findOrFail($id);

        if (!$schedule->active) {
            return response()->json(['error' => 'Schedule is not active'], 400);
        }

        try {
            \App\Jobs\CreateBackupJob::dispatch($schedule)->onQueue('backups');

            return response()->json(['message' => 'Schedule triggered successfully']);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to trigger schedule: ' . $e->getMessage());
        }
    }

    /**
     * Get database tables list.
     */
    public function getTables(): JsonResponse
    {
        $tables = BackupHelper::getTableList();

        return response()->json($tables);
    }

    /**
     * Get storage disks.
     */
    public function getDisks(): JsonResponse
    {
        $disks = [
            ['value' => 'local', 'label' => 'Local Storage'],
            ['value' => 's3', 'label' => 'S3 Storage'],
        ];

        return response()->json($disks);
    }

    /**
     * Webhook endpoint for external schedulers.
     */
    public function webhookTrigger(WebhookTriggerRequest $request): JsonResponse
    {
        try {
            $this->backupService->runDueSchedules();

            return response()->json([
                'success' => true,
                'message' => 'Schedules checked',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return $this->messageService->responseError('Failed to run schedules: ' . $e->getMessage());
        }
    }
}

