<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ticket statuses (renamed from Parent Ticket Statuses). No child ticket statuses / SLA.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ticket_statuses')) {
            Schema::create('ticket_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('label', 100)->nullable();
                $table->boolean('is_closed')->default(false);
                $table->boolean('is_on_hold')->default(false);
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};
