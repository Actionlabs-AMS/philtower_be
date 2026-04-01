<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected $table = 'knowledge_bases';

    protected $fillable = [
        'ticket_request_id',
        'ticket_update_id',
        'topic',
        'solution',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(TicketRequest::class, 'ticket_request_id');
    }

    public function ticketUpdate()
    {
        return $this->belongsTo(TicketUpdate::class, 'ticket_update_id');
    }
}
