<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_requests') || ! Schema::hasTable('ticket_priorities')) {
            return;
        }
        if (Schema::hasColumn('ticket_requests', 'ticket_priority_id')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_priority_id')->nullable()->after('slas_id');
            $table->foreign('ticket_priority_id')->references('id')->on('ticket_priorities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_requests') || ! Schema::hasColumn('ticket_requests', 'ticket_priority_id')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropForeign(['ticket_priority_id']);
            $table->dropColumn('ticket_priority_id');
        });
    }
};
