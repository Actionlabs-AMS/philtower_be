<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_types';

    protected $fillable = [
        'name',
        'code',
        'description',
        'active',
        'approval',
        'parent_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'approval' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ServiceType::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ServiceType::class, 'parent_id');
    }

    public function scopeChildTypes($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeParentTypes($query)
    {
        return $query->whereNull('parent_id');
    }
}
