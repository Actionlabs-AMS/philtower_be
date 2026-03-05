<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Backup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'status',
        'storage_disk',
        'storage_path',
        'file_size',
        'file_path',
        'encrypted',
        'compression',
        'tables_included',
        'files_included',
        'metadata',
        'error_message',
        'created_by',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
        'file_size' => 'integer',
        'tables_included' => 'array',
        'files_included' => 'array',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that created the backup.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the schedule that triggered this backup (if any).
     * Note: This relationship can be added later if we add schedule_id to backups table
     */
    // public function schedule(): BelongsTo
    // {
    //     return $this->belongsTo(BackupSchedule::class, 'schedule_id');
    // }

    /**
     * Scope a query to only include completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include non-expired backups.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if backup is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if backup can be restored.
     */
    public function canRestore(): bool
    {
        // Check basic conditions
        if ($this->status !== 'completed') {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        // Must have either file_path or storage_path
        if (!$this->file_path && !$this->storage_path) {
            return false;
        }

        // For S3, we can't check file existence easily, so just check if URL exists
        if ($this->storage_disk === 's3') {
            return !empty($this->file_path) || !empty($this->storage_path);
        }

        // For local storage, check if file exists using Storage or file system
        $path = $this->getStoragePath();
        
        // If path is empty, try to get it from file_path
        if (empty($path)) {
            $path = $this->file_path;
        }

        if (empty($path)) {
            return false;
        }

        $disk = $this->storage_disk ?: 'local';
        
        try {
            // Try using Storage facade first
            if (Storage::disk($disk)->exists($path)) {
                return true;
            }
        } catch (\Exception $e) {
            // If Storage check fails, fall through to file_exists
        }

        // Fallback to file_exists for absolute paths
        $fullPath = $this->getFullPath();
        if (!empty($fullPath) && file_exists($fullPath)) {
            return true;
        }

        return false;
    }

    /**
     * Get storage path (relative path for Storage facade).
     */
    public function getStoragePath(): string
    {
        // Prefer storage_path if it's set and not empty (it's the relative path)
        if (!empty($this->storage_path)) {
            return $this->storage_path;
        }

        // If file_path is already a relative path (doesn't start with / or storage_path), return as is
        if (!empty($this->file_path) && !str_starts_with($this->file_path, '/') && !str_starts_with($this->file_path, storage_path())) {
            return $this->file_path;
        }

        // If file_path is a full path, extract the relative part
        if (!empty($this->file_path)) {
            $appPath = storage_path('app/');
            if (str_starts_with($this->file_path, $appPath)) {
                $relative = str_replace($appPath, '', $this->file_path);
                // Normalize path separators for cross-platform compatibility
                return str_replace('\\', '/', $relative);
            }
            
            // If it's a full path but not under storage/app, try to extract just the filename
            // This handles edge cases where the path might be stored differently
            if (str_contains($this->file_path, 'backups/')) {
                $parts = explode('backups/', $this->file_path, 2);
                if (isset($parts[1])) {
                    return 'backups/' . str_replace('\\', '/', $parts[1]);
                }
            }
        }

        // Fallback to file_path (might be empty, but that's handled by caller)
        return $this->file_path ?: '';
    }

    /**
     * Get full file path.
     */
    public function getFullPath(): string
    {
        if ($this->storage_disk === 's3') {
            return $this->file_path; // S3 URL
        }

        // If file_path is already a full path, return it
        if ($this->file_path && (str_starts_with($this->file_path, '/') || str_starts_with($this->file_path, storage_path()))) {
            return $this->file_path;
        }

        // Otherwise, construct the full path from relative path
        $relativePath = $this->file_path ?: $this->storage_path;
        return storage_path('app/' . ltrim($relativePath, '/'));
    }

    /**
     * Mark backup as completed.
     */
    public function markAsCompleted(int $fileSize = null, string $filePath = null): void
    {
        $this->update([
            'status' => 'completed',
            'file_size' => $fileSize ?? $this->file_size,
            'file_path' => $filePath ?? $this->file_path,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark backup as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}

