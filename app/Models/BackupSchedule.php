<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Cron\CronExpression as CronExpressionLib;

class BackupSchedule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'frequency',
        'cron_expression',
        'time',
        'day_of_week',
        'day_of_month',
        'retention_days',
        'storage_disk',
        'encrypted',
        'compression',
        'tables_included',
        'files_included',
        'active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'encrypted' => 'boolean',
        'active' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'retention_days' => 'integer',
        'tables_included' => 'array',
        'files_included' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created the schedule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get backups created by this schedule.
     * Note: This relationship can be added later if we add schedule_id to backups table
     */
    // public function backups(): HasMany
    // {
    //     return $this->hasMany(Backup::class, 'schedule_id');
    // }

    /**
     * Generate cron expression from frequency settings.
     */
    public function getCronExpression(): ?string
    {
        if ($this->frequency === 'custom' && $this->cron_expression) {
            return $this->cron_expression;
        }

        $time = $this->time ? Carbon::parse($this->time) : Carbon::parse('02:00');
        $hour = $time->hour;
        $minute = $time->minute;

        switch ($this->frequency) {
            case 'daily':
                return sprintf('%d %d * * *', $minute, $hour);

            case 'weekly':
                $dayOfWeek = $this->day_of_week ?? 1; // Monday default
                return sprintf('%d %d * * %d', $minute, $hour, $dayOfWeek);

            case 'monthly':
                $dayOfMonth = $this->day_of_month ?? 1;
                return sprintf('%d %d %d * *', $minute, $hour, $dayOfMonth);

            default:
                return null;
        }
    }

    /**
     * Calculate next run time.
     */
    public function calculateNextRun(): ?Carbon
    {
        if (!$this->active) {
            return null;
        }

        $now = now();

        if ($this->frequency === 'custom' && $this->cron_expression) {
            try {
                $cron = CronExpressionLib::factory($this->cron_expression);
                return Carbon::instance($cron->getNextRunDate($now));
            } catch (\Exception $e) {
                \Log::error("Invalid cron expression for schedule {$this->id}: " . $e->getMessage());
                return null;
            }
        }

        $time = $this->time ? Carbon::parse($this->time) : Carbon::parse('02:00');
        $next = $now->copy();

        switch ($this->frequency) {
            case 'daily':
                $next->setTime($time->hour, $time->minute, 0);
                if ($next->isPast()) {
                    $next->addDay();
                }
                return $next;

            case 'weekly':
                $dayOfWeek = $this->day_of_week ?? 1;
                $next->next($dayOfWeek);
                $next->setTime($time->hour, $time->minute, 0);
                if ($next->isPast()) {
                    $next->addWeek();
                }
                return $next;

            case 'monthly':
                $dayOfMonth = $this->day_of_month ?? 1;
                $next->day($dayOfMonth);
                $next->setTime($time->hour, $time->minute, 0);
                if ($next->isPast()) {
                    $next->addMonth();
                }
                // Handle months with fewer days
                if ($next->day !== $dayOfMonth) {
                    $next->lastOfMonth();
                }
                return $next;

            default:
                return null;
        }
    }

    /**
     * Check if schedule should run now.
     */
    public function shouldRun(): bool
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->next_run_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->next_run_at);
    }

    /**
     * Update next run time.
     */
    public function updateNextRun(): void
    {
        $this->update([
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }
}

