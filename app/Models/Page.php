<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
	use SoftDeletes;
	
	/**
	 * The attributes that are mass assignable.
	 * 
	 * Note: 
	 * - layout_structure: Primary content storage for page builder (JSON with nested blocks including columns)
	 * - content: Legacy field kept for backward compatibility
	 * - layout: Always 'default' for page builder, kept for backward compatibility
	 * - featured_image: Legacy field, images now added via page builder blocks
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'title',
		'slug',
		'content', // Legacy field
		'layout_structure', // Primary content storage for page builder
		'layout', // Always 'default' for page builder
		'author_id',
		'featured_image', // Legacy field
		'meta_title',
		'meta_description',
		'status',
		'published_at',
		'active'
	];

    /**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
		'published_at' => 'datetime',
		'layout_structure' => 'array',
	];

  /**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'pages';

	/**
	 * Get the author (user) that created the page.
	 */
	public function author()
	{
		return $this->belongsTo(\App\Models\User::class, 'author_id');
	}

	/**
	 * Get the author name.
	 */
	public function getAuthorNameAttribute()
	{
		return $this->author ? $this->author->user_login : null;
	}
}

