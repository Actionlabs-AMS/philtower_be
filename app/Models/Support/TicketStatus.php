<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ticket status master (renamed from Parent Ticket Status).
 */
class TicketStatus extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_statuses';

    /** Table has no created_at/updated_at columns */
    public $timestamps = false;

    protected $fillable = [
        'code',
        'label',
        'is_closed',
        'is_on_hold',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
        'is_on_hold' => 'boolean',
        'deleted_at' => 'datetime',
    ];
}
