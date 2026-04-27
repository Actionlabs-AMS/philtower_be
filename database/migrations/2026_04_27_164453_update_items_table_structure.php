<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            // ➕ add foreign keys
            $table->foreignId('category_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->nullable()
                ->after('subcategory_id')
                ->constrained('items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {

            // ❌ drop foreign keys first
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
            $table->dropForeign(['item_id']);

            // ❌ drop columns
            $table->dropColumn(['category_id', 'subcategory_id', 'item_id']);

            // ➕ restore old column
            $table->unsignedBigInteger('parent_ticket_id')->nullable();
        });
    }
};