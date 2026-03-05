<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
      'parent_id' => $this->parent_id,
      'parent_category' => $this->getParent,
      'parent_category_name' => ($this->getParent) ? $this->getParent->name : null,
      'name' => $this->name,
      'slug' => $this->slug,
      'short_desc' => (strlen($this->descriptions) > 50) ? substr($this->descriptions, 0, 45) . '...' : $this->descriptions,
      'descriptions' => $this->descriptions,
      'active' => ($this->active) ? 'Active' : 'Inactive',
      'label' => $this->label,
      'children' => self::collection($this->getChildren),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}
