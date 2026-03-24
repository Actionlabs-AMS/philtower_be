<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
