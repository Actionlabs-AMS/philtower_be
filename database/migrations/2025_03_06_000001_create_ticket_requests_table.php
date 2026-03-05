<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * All Tickets: ticket_requests table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ticket_requests')) {
            Schema::create('ticket_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number')->unique()->index();

                // Main references
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('parent_ticket_id')->nullable()->index();
                $table->unsignedBigInteger('service_type_id')->nullable()->index();

                // Request details
                $table->mediumText('description')->nullable();

                // Attachments
                $table->json('attachment_metadata')->nullable();

                // Contact details
                $table->string('contact_number')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_email')->nullable();

                // Workflow + Assignment
                $table->unsignedBigInteger('ticket_status_id')->default(1)->index();
                $table->unsignedBigInteger('slas_id')->default(3)->index();
                $table->tinyInteger('for_approval')->default(3)->index(); // 1=yes, 2=no, 3=auto
                $table->unsignedBigInteger('assigned_to')->nullable()->index();

                // Timeline
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_requests');
    }
};
