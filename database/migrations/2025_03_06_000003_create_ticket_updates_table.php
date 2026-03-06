<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ticket updates (comments, status changes, notes) for ticket_requests.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ticket_updates')) {
            Schema::create('ticket_updates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_request_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('content')->nullable();
                $table->string('type', 32)->default('comment')->index();
                $table->boolean('is_internal')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('ticket_request_id')->references('id')->on('ticket_requests')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_updates');
    }
};
