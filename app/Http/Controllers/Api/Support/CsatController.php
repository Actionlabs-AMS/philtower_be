<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\TicketRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsatController extends Controller
{
    /**
     * Record a CSAT rating for a ticket via token (public, no auth required).
     */
    public function rate(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $ticket = TicketRequest::where('csat_token', $token)->first();

        if (! $ticket) {
            return response()->json(['message' => 'Invalid or expired survey link.'], 404);
        }

        if ($ticket->csat_rating !== null) {
            return response()->json(['message' => 'You have already submitted your rating. Thank you!'], 200);
        }

        $ticket->csat_rating = (int) $request->input('rating');
        $ticket->save();

        return response()->json(['message' => 'Thank you for your feedback!'], 200);
    }
}
