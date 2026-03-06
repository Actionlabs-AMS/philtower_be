<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketRequest extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_requests';

    protected $fillable = [
        'request_number',
        'user_id',
        'parent_ticket_id',
        'service_type_id',
        'description',
        'attachment_metadata',
        'contact_number',
        'contact_name',
        'contact_email',
        'ticket_status_id',
        'slas_id',
        'for_approval',
        'assigned_to',
        'submitted_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'attachment_metadata' => 'array',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const FOR_APPROVAL_YES = 1;
    public const FOR_APPROVAL_NO = 2;
    public const FOR_APPROVAL_AUTO = 3;

    /**
     * Human-readable created_at (e.g. "Mar 6, 2026 12:57 PM").
     */
    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at?->format('M j, Y g:i A');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_number)) {
                $model->request_number = static::generateRequestNumber();
            }
        });
    }

    public static function generateRequestNumber(): string
    {
        $prefix = 'TR';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . '-' . $date . '-' . $random;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function ticketStatus()
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    public function sla()
    {
        return $this->belongsTo(Sla::class, 'slas_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * SLA clocks for this ticket request (entity_type = ticket_request, entity_id = id).
     */
    public function slaClocks()
    {
        return $this->hasMany(SlaClock::class, 'entity_id')->where('entity_type', 'ticket_request');
    }
}
