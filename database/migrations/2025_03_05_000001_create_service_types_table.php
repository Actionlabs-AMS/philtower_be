<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_types')) {
            Schema::create('service_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('description')->nullable();
                $table->boolean('active')->default(true);
                $table->boolean('approval')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
            Schema::table('service_types', function (Blueprint $table) {
                $table->foreign('parent_id')->references('id')->on('service_types')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('service_types');
    }
};
