<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Category;


class ItemResource extends JsonResource
{
    /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
    public function toArray(Request $request): array
    {
        $subcategoryIds = $this->subcategory_id ?? [];

        // Fetch names from categories table
        $subcategoryNames = Category::whereIn('id', $subcategoryIds)
            ->pluck('name')
            ->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'active' => $this->active,

            // keep original ids (optional but useful)
            'subcategory_id' => $subcategoryIds,

            // ✅ THIS is what your table will use
            'subcategory_names' => $subcategoryNames,

            'updated_at' => $this->updated_at,
        ];
    }
}
