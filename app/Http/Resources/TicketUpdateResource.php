<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketUpdateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $author = $this->whenLoaded('author');

        return [
            'id' => $this->id,
            'ticket_request_id' => $this->ticket_request_id,
            'parent_update_id' => $this->parent_update_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'is_internal' => $this->is_internal,
            'author' => $this->when($author !== null, function () use ($author) {
                $firstName = $author->first_name ?? $author->user_login ?? null;
                $lastName = $author->last_name ?? null;
                $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: ($author->user_login ?? 'Agent');
                return [
                    'id' => $author->id ?? null,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'user_login' => $author->user_login ?? null,
                    'user_email' => $author->user_email ?? null,
                    'full_name' => $fullName,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'replies' => TicketUpdateResource::collection($this->whenLoaded('replies')),
        ];
    }
}
