<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $backup = Backup::create([
            'name' => 'Test Backup',
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'name' => 'Test Backup',
            'type' => 'database',
        ]);
    }

    public function test_backup_has_creator_relationship(): void
    {
        $user = User::factory()->create();
        
        $backup = Backup::create([
            'name' => 'Test Backup',
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $backup->creator);
        $this->assertEquals($user->id, $backup->creator->id);
    }

    public function test_backup_scopes_work(): void
    {
        $user = User::factory()->create();
        
        Backup::create([
            'name' => 'Completed Backup',
            'type' => 'database',
            'status' => 'completed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        Backup::create([
            'name' => 'Failed Backup',
            'type' => 'database',
            'status' => 'failed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test2.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $this->assertEquals(1, Backup::completed()->count());
        $this->assertEquals(1, Backup::failed()->count());
        // Both backups are database type, so count should be 2
        $this->assertEquals(2, Backup::byType('database')->count());
    }

    public function test_backup_is_expired(): void
    {
        $user = User::factory()->create();
        
        $backup = Backup::create([
            'name' => 'Expired Backup',
            'type' => 'database',
            'status' => 'completed',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'expires_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($backup->isExpired());
    }

    public function test_backup_can_mark_as_completed(): void
    {
        $user = User::factory()->create();
        
        $backup = Backup::create([
            'name' => 'Test Backup',
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $backup->markAsCompleted(1024, 'backups/test.sql.gz');

        $this->assertEquals('completed', $backup->fresh()->status);
        $this->assertEquals(1024, $backup->fresh()->file_size);
        $this->assertNotNull($backup->fresh()->completed_at);
    }

    public function test_backup_can_mark_as_failed(): void
    {
        $user = User::factory()->create();
        
        $backup = Backup::create([
            'name' => 'Test Backup',
            'type' => 'database',
            'status' => 'pending',
            'storage_disk' => 'local',
            'storage_path' => 'backups/test.sql',
            'compression' => 'gzip',
            'encrypted' => false,
            'created_by' => $user->id,
        ]);

        $backup->markAsFailed('Test error message');

        $this->assertEquals('failed', $backup->fresh()->status);
        $this->assertEquals('Test error message', $backup->fresh()->error_message);
    }
}

