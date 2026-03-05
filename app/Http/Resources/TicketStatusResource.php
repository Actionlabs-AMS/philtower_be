<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'is_closed' => (bool) $this->is_closed,
            'is_on_hold' => (bool) $this->is_on_hold,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
