<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_update_id')->nullable()->after('ticket_request_id');
            $table->foreign('parent_update_id')->references('id')->on('ticket_updates')->nullOnDelete();
            $table->index('parent_update_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_updates', function (Blueprint $table) {
            $table->dropForeign(['parent_update_id']);
            $table->dropIndex(['parent_update_id']);
            $table->dropColumn('parent_update_id');
        });
    }
};
