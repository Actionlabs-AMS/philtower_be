<?php

namespace App\Events;

use App\Models\Support\TicketRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TicketRequest $ticket,
        public ?string $oldStatusLabel,
        public ?string $newStatusLabel,
        public ?int $oldStatusId,
        public ?int $newStatusId
    ) {}
}
