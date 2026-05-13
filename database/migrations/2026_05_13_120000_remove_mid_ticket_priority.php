<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove "Mid" / "Medium" ticket priority: reassign affected tickets to Low, then delete the row.
     * FK on ticket_requests is nullOnDelete(), so tickets must be updated before delete.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ticket_priorities') || ! Schema::hasTable('ticket_requests')) {
            return;
        }

        $midId = DB::table('ticket_priorities')
            ->whereRaw('LOWER(TRIM(label)) IN (?, ?, ?)', ['mid', 'medium', 'med'])
            ->orderBy('id')
            ->value('id');

        if (! $midId) {
            return;
        }

        $lowId = DB::table('ticket_priorities')
            ->whereRaw('LOWER(TRIM(label)) = ?', ['low'])
            ->value('id');

        DB::transaction(function () use ($midId, $lowId) {
            if ($lowId) {
                DB::table('ticket_requests')
                    ->where('ticket_priority_id', $midId)
                    ->update(['ticket_priority_id' => $lowId]);
            } else {
                DB::table('ticket_requests')
                    ->where('ticket_priority_id', $midId)
                    ->update(['ticket_priority_id' => null]);
            }

            DB::table('ticket_priorities')->where('id', $midId)->delete();
        });
    }

    /**
     * Restore the Mid catalog row only (cannot restore which tickets were Mid vs Low).
     */
    public function down(): void
    {
        if (! Schema::hasTable('ticket_priorities')) {
            return;
        }

        $exists = DB::table('ticket_priorities')
            ->whereRaw('LOWER(TRIM(label)) IN (?, ?, ?)', ['mid', 'medium', 'med'])
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('ticket_priorities')->insert([
            'id' => 3,
            'label' => 'Mid',
            'level' => 3.0,
        ]);
    }
};
