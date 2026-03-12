<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserMeta;
use App\Models\Role;
use App\Traits\Encryptable;
use App\Traits\Anonymizable;

class User extends Authenticatable
{
	use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Encryptable, Anonymizable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_login',
		'user_email',
		'user_pass',
		'user_salt',
		'user_status',
		'user_activation_key',
		'role_id',
		'remember_token',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'user_pass',
		'user_salt',
		'user_activation_key',
		'remember_token',
	];

	/**
	 * Initialize the model.
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
	}

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
	protected $table = 'users';

	public function saveUserMeta($metaData) 
	{
		// Debug: Log meta data being saved
		\Log::info('[User Model] saveUserMeta called:', [
			'user_id' => $this->id,
			'meta_data' => $metaData,
			'meta_keys' => array_keys($metaData),
		]);

		foreach ($metaData as $key => $data) {
			$result = UserMeta::updateOrCreate(
			[
				'user_id' => $this->id,
				'meta_key' => $key
			],
			[
				'meta_value' => $data,
			]);

			// Debug: Log each meta save
			\Log::info('[User Model] Meta saved:', [
				'user_id' => $this->id,
				'meta_key' => $key,
				'meta_value' => $data,
				'was_recently_created' => $result->wasRecentlyCreated,
			]);
		}

		// Debug: Verify saved meta data
		$savedMeta = UserMeta::where('user_id', $this->id)
			->whereIn('meta_key', array_keys($metaData))
			->get()
			->pluck('meta_value', 'meta_key')
			->toArray();
		
		\Log::info('[User Model] Verified saved meta:', [
			'user_id' => $this->id,
			'saved_meta' => $savedMeta,
		]);
	}

	/**
	 * Append additiona info to the return data
	 *
	 * @var string
	 */
	public $appends = [
		'user_details',
		'user_role',
	];

	public function getUserMetas()
	{   
		return $this->hasMany('App\Models\UserMeta', 'user_id', 'id');
	}

	/**
	 * Get the role that belongs to the user.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function role()
	{
		return $this->belongsTo(Role::class, 'role_id');
	}

	public function getUserRole($role_id)
	{   
		return Role::find($role_id);
	}

	/****************************************
	*           ATTRIBUTES PARTS            *
	****************************************/
	public function getUserDetailsAttribute()
	{
		return $this->getUserMetas()->pluck('meta_value', 'meta_key')->toArray();
	}

	public function getUserRoleAttribute()
	{
		// Use the role relationship instead of user_meta
		return $this->role;
	}

	/**
	 * Get a specific meta value for the user
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getMeta($key)
	{
		$meta = $this->getUserMetas()->where('meta_key', $key)->first();
		return $meta ? $meta->meta_value : null;
	}

	/**
	 * Whether the user can see all tickets (vs. only assigned).
	 * Users without this (e.g. non–super-admin) see only tickets assigned to them.
	 */
	public function canViewAllTickets(): bool
	{
		return $this->role && $this->role->is_super_admin;
	}
}
