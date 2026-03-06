<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Resources\TicketUpdateResource;
use App\Models\Support\TicketRequest;
use App\Models\Support\TicketUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketUpdateController
{
    /**
     * List updates for a ticket request.
     */
    public function index(int $id): JsonResponse
    {
        $ticket = TicketRequest::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $perPage = (int) request('per_page', 50);
        $updates = TicketUpdate::where('ticket_request_id', $id)
            ->with('author')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return TicketUpdateResource::collection($updates)->response();
    }

    /**
     * Store a new update (comment) for a ticket request.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $ticket = TicketRequest::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:65535',
            'type' => 'nullable|string|in:comment,status_change,note',
            'is_internal' => 'nullable|boolean',
        ]);

        $validated['ticket_request_id'] = $id;
        $validated['type'] = $validated['type'] ?? TicketUpdate::TYPE_COMMENT;
        $validated['is_internal'] = $validated['is_internal'] ?? false;

        $update = TicketUpdate::create($validated);
        $update->load('author');

        return response()->json([
            'success' => true,
            'message' => 'Update added',
            'data' => new TicketUpdateResource($update),
        ], 201);
    }
}
