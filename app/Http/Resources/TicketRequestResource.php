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
            'created_by' => $this->created_by,
            'parent_ticket_id' => $this->parent_ticket_id,
            'service_type_id' => $this->service_type_id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'item_id' => $this->item_id,
            'description' => $this->description,
            'attachment_metadata' => $this->attachment_metadata,
            'contact_number' => $this->contact_number,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'ticket_status_id' => $this->ticket_status_id,
            'slas_id' => $this->slas_id,
            'ticket_priority_id' => $this->ticket_priority_id,
            'for_approval' => $this->for_approval,
            'manual_approval_data' => $this->manual_approval_data,
            'assigned_to' => $this->assigned_to,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at_human,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'last_updated_at_human' => $this->updated_at?->format('M j, Y g:i A'),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'sla_breached' => (bool) $this->slaClocks()
                ->where('status', 'breached')
                ->exists(),
            // Flat labels for list/table (when relations loaded)
            'ticket_status_label' => $this->whenLoaded('ticketStatus', fn () => $this->ticketStatus?->label),
            'ticket_priority_label' => $this->whenLoaded('ticketPriority', fn () => $this->ticketPriority?->label),
            'service_type_name' => $this->whenLoaded('serviceType', fn () => $this->serviceType?->name),
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'subcategory_name' => $this->whenLoaded('subcategory', fn () => $this->subcategory?->name),
            'item_name' => $this->whenLoaded('item', fn () => $this->item?->name),
            'assigned_to_name' => $this->whenLoaded('assignedTo', function () {
                if (! $this->assignedTo) {
                    return null;
                }
                try {
                    if (method_exists($this->assignedTo, 'getMeta')) {
                        $first = $this->assignedTo->getMeta('first_name');
                        $last = $this->assignedTo->getMeta('last_name');
                        $name = trim(($first ?? '') . ' ' . ($last ?? ''));
                        if ($name !== '') {
                            return $name;
                        }
                    }
                } catch (\Throwable $e) {
                    // Fall through to user_login
                }
                return $this->assignedTo->user_login ?? null;
            }),
            'created_by_name' => $this->whenLoaded('createdBy', function () {
                if (! $this->createdBy) {
                    return null;
                }
                try {
                    if (method_exists($this->createdBy, 'getMeta')) {
                        $first = $this->createdBy->getMeta('first_name');
                        $last = $this->createdBy->getMeta('last_name');
                        $name = trim(($first ?? '') . ' ' . ($last ?? ''));
                        if ($name !== '') {
                            return $name;
                        }
                    }
                } catch (\Throwable $e) {
                    // Fall through to user_login
                }
                return $this->createdBy->user_login ?? null;
            }),
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
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'code' => $this->category->code,
            ] : null),
            'subcategory' => $this->whenLoaded('subcategory', fn () => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'code' => $this->subcategory->code,
            ] : null),
            'item' => $this->whenLoaded('item', fn () => $this->item ? [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'code' => $this->item->code,
            ] : null),
            'sla' => $this->whenLoaded('sla', fn () => $this->sla ? [
                'id' => $this->sla->id,
                'severity' => $this->sla->severity,
                'response_minutes' => $this->sla->response_minutes,
                'resolution_minutes' => $this->sla->resolution_minutes,
            ] : null),
            'ticket_priority' => $this->whenLoaded('ticketPriority', fn () => $this->ticketPriority ? [
                'id' => $this->ticketPriority->id,
                'label' => $this->ticketPriority->label,
                'level' => $this->ticketPriority->level,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'user_login' => $this->user->user_login,
                'user_email' => $this->user->user_email,
            ] : null),
            'assigned_to_user' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'user_login' => $this->assignedTo->user_login,
                'user_email' => $this->assignedTo->user_email,
            ] : null),
        ];
    }
}
