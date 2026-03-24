<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->json('manual_approval_data')->nullable()->after('for_approval');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropColumn('manual_approval_data');
        });
    }
};
