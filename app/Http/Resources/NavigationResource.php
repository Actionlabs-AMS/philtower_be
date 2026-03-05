<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavigationResource extends JsonResource
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
      'name' => $this->name,
      'label' => $this->label,
      'slug' => $this->slug,
      'icon' => $this->icon,
      'parent_navigation' => $this->parent,
      'parent_navigation_name' => ($this->parent) ? $this->parent->name : null,
      'active' => ($this->active) ? 'Active' : 'Inactive',
      'show_in_menu' => ($this->show_in_menu) ? 'Show' : 'Hidden',
      'children' => NavigationResource::collection($this->whenLoaded('children')),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}
