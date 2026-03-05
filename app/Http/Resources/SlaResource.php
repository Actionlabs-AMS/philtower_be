<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $conditions = $this->pause_conditions ?? [];
        return [
            'id' => $this->id,
            'severity' => $this->severity,
            'response_minutes' => $this->response_minutes,
            'resolution_minutes' => $this->resolution_minutes,
            'pause_on_onhold' => (bool) ($conditions['on_onhold'] ?? false),
            'pause_on_fe_visit' => (bool) ($conditions['on_fe_visit'] ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
