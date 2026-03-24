<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_ticket_id');
            $table->unsignedBigInteger('target_ticket_id');
            $table->enum('relationship_type', ['duplicate_of', 'parent_of', 'child_of', 'relates_to']);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('source_ticket_id')->references('id')->on('ticket_requests')->cascadeOnDelete();
            $table->foreign('target_ticket_id')->references('id')->on('ticket_requests')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['source_ticket_id', 'target_ticket_id', 'relationship_type'], 'ticket_rel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_relationships');
    }
};
