<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\KnowledgeBase;
use App\Models\Support\TicketUpdate;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->query('search', '');
        $query = KnowledgeBase::query()->where('status', 'approved');
        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('topic', 'like', '%' . $q . '%')
                    ->orWhere('solution', 'like', '%' . $q . '%');
            });
        }
        return response()->json(['data' => $query->latest('approved_at')->paginate((int) $request->query('per_page', 20))]);
    }

    public function pending(Request $request)
    {
        return response()->json([
            'data' => KnowledgeBase::query()
                ->where('status', 'pending')
                ->latest('created_at')
                ->paginate((int) $request->query('per_page', 20)),
        ]);
    }

    public function approve($id)
    {
        $item = KnowledgeBase::findOrFail((int) $id);
        $item->status = 'approved';
        $item->approved_by = auth()->id();
        $item->approved_at = now();
        $item->save();
        return response()->json(['data' => $item]);
    }

    public function reject($id)
    {
        $item = KnowledgeBase::findOrFail((int) $id);
        $item->status = 'rejected';
        $item->approved_by = auth()->id();
        $item->approved_at = now();
        $item->save();
        return response()->json(['data' => $item]);
    }

    public function tagFromUpdate(Request $request, $ticketId, $updateId)
    {
        $update = TicketUpdate::query()
            ->where('ticket_request_id', (int) $ticketId)
            ->where('id', (int) $updateId)
            ->firstOrFail();
        $kb = KnowledgeBase::updateOrCreate(
            ['ticket_update_id' => $update->id],
            [
                'ticket_request_id' => $update->ticket_request_id,
                'topic' => (string) ($request->input('topic') ?: ('From ticket ' . $update->ticket_request_id)),
                'solution' => $update->content,
                'status' => 'pending',
            ]
        );
        return response()->json(['data' => $kb]);
    }
}
