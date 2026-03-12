<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add metadata column for structured activity log data (e.g. reassignment old/new assignee).
     */
    public function up(): void
    {
        Schema::table('ticket_updates', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_updates', 'metadata')) {
                $table->json('metadata')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_updates', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
