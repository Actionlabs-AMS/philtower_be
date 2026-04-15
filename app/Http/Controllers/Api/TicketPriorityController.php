<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketPriorityRequest;
use App\Http\Requests\UpdateTicketPriorityRequest;
use App\Http\Resources\TicketPriorityResource;
use App\Models\Support\TicketPriority;
use App\Services\TicketPriorityService;

class TicketPriorityController extends Controller
{
    public function __construct(private TicketPriorityService $service)
    {
    }

    public function index()
    {
        $priorities = $this->service->getAllPriorities();

        return TicketPriorityResource::collection($priorities);
    }

    public function store(StoreTicketPriorityRequest $request)
    {
        $priority = $this->service->createPriority($request->validated());

        return new TicketPriorityResource($priority);
    }

    public function show(TicketPriority $ticketPriority)
    {
        return new TicketPriorityResource($ticketPriority);
    }

    public function update(UpdateTicketPriorityRequest $request, TicketPriority $ticketPriority)
    {
        $priority = $this->service->updatePriority($ticketPriority, $request->validated());

        return new TicketPriorityResource($priority);
    }

    public function destroy(TicketPriority $ticketPriority)
    {
        $this->service->deletePriority($ticketPriority);

        return response()->json(null, 204);
    }
}
