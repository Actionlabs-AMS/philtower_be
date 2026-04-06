<?php

namespace App\Services;

use App\Models\Support\TicketPriority;

class TicketPriorityService
{
    public function getAllPriorities()
    {
        return TicketPriority::orderBy('level', 'asc')->get();
    }

    public function createPriority(array $data): TicketPriority
    {
        return TicketPriority::create($data);
    }

    public function updatePriority(TicketPriority $priority, array $data): TicketPriority
    {
        $priority->update($data);

        return $priority;
    }

    public function deletePriority(TicketPriority $priority): void
    {
        $priority->delete();
    }

    public function getPriorityByLevel(float $level): ?TicketPriority
    {
        return TicketPriority::where('level', $level)->first();
    }
}
