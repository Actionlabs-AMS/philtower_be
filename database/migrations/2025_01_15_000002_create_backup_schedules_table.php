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
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('type', ['database', 'files', 'full'])->default('full');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->string('cron_expression', 100)->nullable();
            $table->time('time')->nullable();
            $table->tinyInteger('day_of_week')->nullable(); // 0-6 (0=Sunday)
            $table->tinyInteger('day_of_month')->nullable(); // 1-31
            $table->integer('retention_days')->unsigned()->default(30);
            $table->string('storage_disk', 50)->default('local');
            $table->boolean('encrypted')->default(false);
            $table->enum('compression', ['none', 'gzip', 'zip'])->default('gzip');
            $table->text('tables_included')->nullable(); // JSON array
            $table->text('files_included')->nullable(); // JSON array
            $table->boolean('active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamps();

            $table->index('active', 'idx_active');
            $table->index('next_run_at', 'idx_next_run_at');

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
        Schema::dropIfExists('backup_schedules');
    }
};

