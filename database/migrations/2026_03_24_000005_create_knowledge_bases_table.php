<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_request_id')->nullable();
            $table->unsignedBigInteger('ticket_update_id')->nullable();
            $table->string('topic', 255)->nullable();
            $table->longText('solution');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_request_id')->references('id')->on('ticket_requests')->nullOnDelete();
            $table->foreign('ticket_update_id')->references('id')->on('ticket_updates')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
