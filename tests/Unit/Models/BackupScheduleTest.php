<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\BackupSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $schedule = BackupSchedule::create([
            'name' => 'Daily Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'storage_disk' => 'local',
            'compression' => 'gzip',
            'encrypted' => false,
            'active' => true,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Daily Backup',
            'frequency' => 'daily',
        ]);
    }

    public function test_schedule_generates_cron_expression_for_daily(): void
    {
        $user = User::factory()->create();
        
        $schedule = BackupSchedule::create([
            'name' => 'Daily Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:30',
            'retention_days' => 30,
            'storage_disk' => 'local',
            'compression' => 'gzip',
            'encrypted' => false,
            'active' => true,
            'created_by' => $user->id,
        ]);

        $cron = $schedule->getCronExpression();
        $this->assertEquals('30 2 * * *', $cron);
    }

    public function test_schedule_calculates_next_run_for_daily(): void
    {
        $user = User::factory()->create();
        
        $schedule = BackupSchedule::create([
            'name' => 'Daily Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'storage_disk' => 'local',
            'compression' => 'gzip',
            'encrypted' => false,
            'active' => true,
            'created_by' => $user->id,
        ]);

        $nextRun = $schedule->calculateNextRun();
        
        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertEquals('02:00', $nextRun->format('H:i'));
    }

    public function test_schedule_should_run_when_due(): void
    {
        $user = User::factory()->create();
        
        $schedule = BackupSchedule::create([
            'name' => 'Daily Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'storage_disk' => 'local',
            'compression' => 'gzip',
            'encrypted' => false,
            'active' => true,
            'next_run_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($schedule->shouldRun());
    }

    public function test_schedule_should_not_run_when_inactive(): void
    {
        $user = User::factory()->create();
        
        $schedule = BackupSchedule::create([
            'name' => 'Daily Backup',
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'storage_disk' => 'local',
            'compression' => 'gzip',
            'encrypted' => false,
            'active' => false,
            'next_run_at' => now()->subMinute(),
            'created_by' => $user->id,
        ]);

        $this->assertFalse($schedule->shouldRun());
    }
}

