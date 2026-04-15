<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;

class TicketPriority extends Model
{
    public $timestamps = false;

    protected $table = 'ticket_priorities';

    protected $fillable = [
        'label',
        'level',
    ];

    protected $casts = [
        'level' => 'float',
    ];

    public function ticketRequests()
    {
        return $this->hasMany(TicketRequest::class, 'ticket_priority_id');
    }
}
