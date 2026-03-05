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
        // Create languages table
        Schema::create('languages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name'); // e.g., "English", "Spanish"
            $table->string('code', 10)->unique(); // e.g., "en", "es", "fr"
            $table->string('native_name')->nullable(); // e.g., "English", "Español"
            $table->string('flag')->nullable(); // Flag emoji or icon
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create translations table
        Schema::create('translations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('language_id')->unsigned();
            $table->string('key'); // The word/key to translate
            $table->text('value'); // The translated text
            $table->string('group')->nullable(); // Optional grouping (e.g., "common", "errors", "validation")
            $table->text('notes')->nullable(); // Optional notes about the translation
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            $table->index(['language_id', 'key']);
            $table->index('group');
            $table->unique(['language_id', 'key', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};

