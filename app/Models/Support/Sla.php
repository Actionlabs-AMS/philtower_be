<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SLA configuration by severity (pause_conditions JSON for on_hold / FE visit).
 */
class Sla extends Model
{
    use SoftDeletes;

    protected $table = 'slas';

    protected $fillable = [
        'severity',
        'response_minutes',
        'resolution_minutes',
        'pause_conditions',
    ];

    protected $casts = [
        'pause_conditions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pausesOnHold(): bool
    {
        return (bool) ($this->pause_conditions['on_onhold'] ?? false);
    }

    public function pausesOnFeVisit(): bool
    {
        return (bool) ($this->pause_conditions['on_fe_visit'] ?? false);
    }
}
