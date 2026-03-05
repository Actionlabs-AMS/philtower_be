<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      "title" => "required|string|max:255",
      "slug" => "required|string|regex:/^[a-zA-Z0-9\-_\/]+$/|unique:pages,slug,".$this->id,
      "content" => "nullable|string", // Legacy field, kept for backward compatibility
      "layout_structure" => "nullable|array", // Primary content storage for page builder (supports nested blocks)
      "layout" => "nullable|string|in:default,full-width,sidebar-left,sidebar-right,two-column,three-column", // Always 'default' for page builder, kept for backward compatibility
      "author_id" => "nullable|integer|exists:users,id",
      "featured_image" => "nullable|string|max:500", // Legacy field, images now added via page builder blocks
      "meta_title" => "nullable|string|max:255",
      "meta_description" => "nullable|string|max:500",
      "status" => "nullable|string|in:draft,published,scheduled",
      "published_at" => "nullable|date",
      "active" => "nullable|boolean",
    ];
  }

  public function messages(): array
	{
		return [
			"title.required" => "The page title is required.",
			"title.max" => "The page title must not exceed 255 characters.",
			"slug.required" => "The page slug is required.",
			"slug.regex" => "The slug can only contain letters, numbers, hyphens, underscores, and slashes.",
			"slug.unique" => "This slug is already taken.",
			"layout.in" => "The selected layout is invalid.",
			"status.in" => "The selected status is invalid.",
			"author_id.exists" => "The selected author does not exist.",
		];
	}
}

