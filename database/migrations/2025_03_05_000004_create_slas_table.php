<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SLA & Timing (parent only; no child SLA). pause_conditions JSON for on_hold / FE visit.
     */
    public function up(): void
    {
        if (!Schema::hasTable('slas')) {
            Schema::create('slas', function (Blueprint $table) {
                $table->id();
                $table->string('severity', 20)->nullable()->index();
                $table->unsignedInteger('response_minutes')->nullable();
                $table->unsignedInteger('resolution_minutes')->nullable();
                $table->json('pause_conditions')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['severity']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slas');
    }
};
