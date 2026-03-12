<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->uuid('csat_token')->nullable()->unique()->after('closed_at');
            $table->tinyInteger('csat_rating')->nullable()->after('csat_token');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropColumn(['csat_token', 'csat_rating']);
        });
    }
};
