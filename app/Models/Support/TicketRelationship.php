<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketRelationship extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source_ticket_id',
        'target_ticket_id',
        'relationship_type',
        'created_by',
    ];

    public function sourceTicket()
    {
        return $this->belongsTo(TicketRequest::class, 'source_ticket_id');
    }

    public function targetTicket()
    {
        return $this->belongsTo(TicketRequest::class, 'target_ticket_id');
    }
}
