<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        if (! Schema::hasTable('ticket_priorities')) {
            Schema::create('ticket_priorities', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('label');
                $table->float('level');
            });
        }

        $ticketPrioritiesEngine = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'ticket_priorities')
            ->value('ENGINE');

        if (strtolower((string) $ticketPrioritiesEngine) !== 'innodb') {
            DB::statement('ALTER TABLE ticket_priorities ENGINE=InnoDB');
        }

        if (! Schema::hasColumn('ticket_requests', 'ticket_priority_id')) {
            Schema::table('ticket_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('ticket_priority_id')->nullable()->after('slas_id');
            });
        }

        $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'ticket_requests')
            ->where('COLUMN_NAME', 'ticket_priority_id')
            ->where('REFERENCED_TABLE_NAME', 'ticket_priorities')
            ->exists();

        if (! $foreignKeyExists) {
            Schema::table('ticket_requests', function (Blueprint $table) {
                $table->foreign('ticket_priority_id')->references('id')->on('ticket_priorities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_requests') || ! Schema::hasColumn('ticket_requests', 'ticket_priority_id')) {
            return;
        }

        $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'ticket_requests')
            ->where('COLUMN_NAME', 'ticket_priority_id')
            ->where('REFERENCED_TABLE_NAME', 'ticket_priorities')
            ->exists();

        Schema::table('ticket_requests', function (Blueprint $table) use ($foreignKeyExists) {
            if ($foreignKeyExists) {
                $table->dropForeign(['ticket_priority_id']);
            }
            $table->dropColumn('ticket_priority_id');
        });
    }
};
