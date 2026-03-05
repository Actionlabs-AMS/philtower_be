<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
			'user_id' => $this->user_id,
			'file_name' => $this->file_name,
			'file_type' => $this->file_type,
			'file_size' => $this->file_size,
			'formatted_file_size' => $this->formatted_file_size ?? $this->file_size,
			'width' => $this->width,
			'height' => $this->height,
			'file_dimensions' => $this->file_dimensions,
			'file_url' => $this->file_url,
			'thumbnail_url' => $this->thumbnail_url ?? $this->file_url,
			'caption' => $this->caption,
			'short_descriptions' => $this->short_descriptions,
			'extension' => $this->extension ?? pathinfo($this->file_name, PATHINFO_EXTENSION),
			'is_image' => $this->isImage(),
			'is_video' => $this->isVideo(),
			'is_audio' => $this->isAudio(),
			'created_at' => $this->created_at->format('M d, Y'),
			'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
			'created_at_raw' => $this->created_at->toISOString(),
			'updated_at_raw' => $this->updated_at->toISOString(),
		];
	}
}
