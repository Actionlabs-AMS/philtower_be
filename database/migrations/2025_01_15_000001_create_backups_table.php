<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('type', ['database', 'files', 'full'])->default('full');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 500);
            $table->bigInteger('file_size')->unsigned()->nullable();
            $table->string('file_path', 500)->nullable();
            $table->boolean('encrypted')->default(false);
            $table->enum('compression', ['none', 'gzip', 'zip'])->default('gzip');
            $table->text('tables_included')->nullable(); // JSON array
            $table->text('files_included')->nullable(); // JSON array
            $table->text('metadata')->nullable(); // JSON
            $table->text('error_message')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type', 'idx_type');
            $table->index('status', 'idx_status');
            $table->index('created_by', 'idx_created_by');
            $table->index('created_at', 'idx_created_at');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};

