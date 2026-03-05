<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
	use HasFactory;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'dashboard_widgets';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'widget_type',
		'title',
		'data_source',
		'query_config',
		'visualization_config',
		'position_x',
		'position_y',
		'width',
		'height',
		'order_index',
		'active',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'query_config' => 'array',
		'visualization_config' => 'array',
		'active' => 'boolean',
		'position_x' => 'integer',
		'position_y' => 'integer',
		'width' => 'integer',
		'height' => 'integer',
		'order_index' => 'integer',
		'user_id' => 'integer',
	];

	/**
	 * Get the user that owns the widget.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	/**
	 * Scope a query to only include active widgets.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeActive($query)
	{
		return $query->where('active', true);
	}

	/**
	 * Scope a query to filter by user.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param int|null $userId
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeForUser($query, ?int $userId = null)
	{
		if ($userId === null) {
			return $query->whereNull('user_id');
		}
		return $query->where('user_id', $userId);
	}

	/**
	 * Scope a query to order by order_index.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeOrdered($query)
	{
		return $query->orderBy('order_index', 'asc');
	}
}

