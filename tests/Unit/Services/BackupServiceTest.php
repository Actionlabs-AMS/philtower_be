<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BackupService;
use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService();
        Storage::fake('local');
    }

    public function test_can_create_backup_record(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $backup = $this->backupService->createBackup([
            'name' => 'Test Backup',
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => 'local',
            'storage_path' => '',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals('Test Backup', $backup->name);
    }

    public function test_can_create_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $schedule = $this->backupService->createSchedule([
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

        $this->assertInstanceOf(BackupSchedule::class, $schedule);
        $this->assertEquals('Daily Backup', $schedule->name);
        $this->assertNotNull($schedule->next_run_at);
    }

    public function test_can_update_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $schedule = $this->backupService->createSchedule([
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

        $updated = $this->backupService->updateSchedule($schedule->id, [
            'name' => 'Updated Backup',
            'time' => '03:00',
        ]);

        $this->assertEquals('Updated Backup', $updated->name);
        $this->assertEquals('03:00', $updated->time);
    }

    public function test_can_delete_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $schedule = $this->backupService->createSchedule([
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

        $result = $this->backupService->deleteSchedule($schedule->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('backup_schedules', ['id' => $schedule->id]);
    }

    public function test_can_get_backup_stats(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Backup::create([
            'name' => 'Test Backup 1',
            'type' => 'database',
            'status' => 'completed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test1.sql',
            'file_size' => 1024,
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        Backup::create([
            'name' => 'Test Backup 2',
            'type' => 'files',
            'status' => 'failed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test2.zip',
            'compression' => 'zip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $stats = $this->backupService->getBackupStats();

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(1024, $stats['total_size']);
    }

    public function test_can_list_backups_with_filters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Backup::create([
            'name' => 'Database Backup',
            'type' => 'database',
            'status' => 'completed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/db.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        Backup::create([
            'name' => 'Files Backup',
            'type' => 'files',
            'status' => 'completed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/files.zip',
            'compression' => 'zip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $backups = $this->backupService->listBackups(['type' => 'database']);

        $this->assertEquals(1, $backups->total());
        $this->assertEquals('Database Backup', $backups->first()->name);
    }
}

