<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
      'title' => $this->title,
      'slug' => $this->slug,
      'content' => $this->content,
      'short_content' => $this->content ? (strlen($this->content) > 100 ? substr(strip_tags($this->content), 0, 95) . '...' : strip_tags($this->content)) : null,
      'layout_structure' => $this->layout_structure,
      'layout' => $this->layout,
      'author_id' => $this->author_id,
      'author_name' => $this->author_name,
      'author' => $this->author ? [
        'id' => $this->author->id,
        'name' => $this->author->user_login,
      ] : null,
      'featured_image' => $this->featured_image,
      'meta_title' => $this->meta_title,
      'meta_description' => $this->meta_description,
      'status' => $this->status,
      'status_label' => ucfirst($this->status),
      'published_at' => $this->published_at ? $this->published_at->format('Y-m-d H:i:s') : null,
      'active' => ($this->active) ? 'Active' : 'Inactive',
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:i:s') : null
    ];
  }
}

