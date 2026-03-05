<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Navigation extends Model
{
	use HasFactory, SoftDeletes;

	protected $fillable = [
		'name',
		'slug',
		'icon',
		'description',
		'parent_id',
		'active',
		'show_in_menu',
	];

	protected $casts = [
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
		'active' => 'boolean',
		'show_in_menu' => 'boolean',
		'parent_id' => 'integer',
	];

	protected $table = 'navigations';

	public $appends = [
		'label',
		'parent_navigation',
];

	public function parent()
	{
		return $this->belongsTo(Navigation::class, 'parent_id');
	}

	public function children()
	{
		return $this->hasMany(Navigation::class, 'parent_id');
	}

	public function getLabelAttribute()
	{
		return $this->name;
	}

	public function getParentNavigationAttribute()
	{
		return $this->parent()->first(); // Fixing logic
	}

	/**
	 * Recursively load all nested children
	 */
	public static function loadTree($onlyActive = true)
	{
		$query = self::query()->whereNull('parent_id');
		if ($onlyActive) {
			$query->where('active', 1);
		}

		$roots = $query->get();

		foreach ($roots as $root) {
			self::loadChildrenRecursively($root, $onlyActive);
		}

		return $roots;
	}

	protected static function loadChildrenRecursively($node, $onlyActive = true)
	{
		$relation = $node->children();
		if ($onlyActive) {
			$relation->where('active', 1);
		}

		$children = $relation->get();
		$node->setRelation('children', $children);

		foreach ($children as $child) {
			self::loadChildrenRecursively($child, $onlyActive);
		}
	}
}
