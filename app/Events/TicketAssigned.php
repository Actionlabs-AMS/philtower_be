<?php

namespace App\Events;

use App\Models\Support\TicketRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TicketRequest $ticket
    ) {}
}
