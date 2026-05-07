<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use SoftDeletes;
  /**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'parent_id',
		'name',
		'code',
		'descriptions',
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
	];

  /**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'categories';

	/**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
		'label'
	];

	public function getParent()
	{
		return $this->belongsTo(Category::class, 'parent_id');
	}
	
	public function getChildren() 
	{
		return $this->hasMany(Category::class, 'parent_id');
	}

	public function getLabelAttribute() 
	{
		return $this->name;
	}
}
