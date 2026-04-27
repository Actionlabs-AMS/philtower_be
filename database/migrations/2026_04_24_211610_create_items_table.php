<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name', 255);
                // 191 keeps unique index size compatible with older MySQL setups.
                $table->string('code', 191)->unique();
                $table->string('description')->nullable();
                $table->json('subcategory_id')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        $currentEngine = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'items')
            ->value('ENGINE');

        if (strtolower((string) $currentEngine) !== 'innodb') {
            DB::statement('ALTER TABLE items ENGINE=InnoDB');
        }

        DB::statement('ALTER TABLE items MODIFY code VARCHAR(191) NOT NULL');

        $uniqueCodeExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'items')
            ->where('INDEX_NAME', 'items_code_unique')
            ->exists();

        if (! $uniqueCodeExists) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique('code', 'items_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
