<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'meta_key',
		'meta_value'
	];

	/**
	 * The table associated with the model.
	 *
	 * @var string
	*/
	protected $table = 'user_meta';

	public $timestamps = false;
}
