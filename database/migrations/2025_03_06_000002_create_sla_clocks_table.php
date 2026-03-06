<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SLA runtime / clock for ticket requests (audit-grade tracking).
     */
    public function up(): void
    {
        if (!Schema::hasTable('sla_clocks')) {
            Schema::create('sla_clocks', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 64)->index();
                $table->unsignedBigInteger('entity_id')->index();
                $table->unsignedBigInteger('sla_id')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('response_due_at')->nullable();
                $table->timestamp('paused_at')->nullable();
                $table->unsignedInteger('total_paused_minutes')->default(0);
                $table->timestamp('breached_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('status', 32)->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_clocks');
    }
};
