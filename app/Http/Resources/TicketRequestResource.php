<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketRequestResource extends JsonResource
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
            'request_number' => $this->request_number,
            'user_id' => $this->user_id,
            'parent_ticket_id' => $this->parent_ticket_id,
            'service_type_id' => $this->service_type_id,
            'description' => $this->description,
            'attachment_metadata' => $this->attachment_metadata,
            'contact_number' => $this->contact_number,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'ticket_status_id' => $this->ticket_status_id,
            'slas_id' => $this->slas_id,
            'for_approval' => $this->for_approval,
            'assigned_to' => $this->assigned_to,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            // Flat labels for list/table (when relations loaded)
            'ticket_status_label' => $this->whenLoaded('ticketStatus', fn () => $this->ticketStatus?->label),
            'service_type_name' => $this->whenLoaded('serviceType', fn () => $this->serviceType?->name),
            // Optional loaded relations (for forms/detail)
            'ticket_status' => $this->whenLoaded('ticketStatus', fn () => $this->ticketStatus ? [
                'id' => $this->ticketStatus->id,
                'code' => $this->ticketStatus->code,
                'label' => $this->ticketStatus->label,
            ] : null),
            'service_type' => $this->whenLoaded('serviceType', fn () => $this->serviceType ? [
                'id' => $this->serviceType->id,
                'name' => $this->serviceType->name,
                'code' => $this->serviceType->code,
            ] : null),
        ];
    }
}
