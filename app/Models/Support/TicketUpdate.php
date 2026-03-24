<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketUpdate extends Model
{
    use SoftDeletes;

    protected $table = 'ticket_updates';

    protected $fillable = [
        'ticket_request_id',
        'parent_update_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'is_internal',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public const TYPE_COMMENT = 'comment';
    public const TYPE_STATUS_CHANGE = 'status_change';
    public const TYPE_NOTE = 'note';
    public const TYPE_REASSIGNMENT = 'reassignment';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (auth()->check()) {
                $model->user_id = $model->user_id ?? auth()->id();
                $model->created_by = $model->created_by ?? auth()->id();
            }
        });

        static::created(function (self $model) {
            try {
                if ($model->ticket_request_id && $model->type === self::TYPE_STATUS_CHANGE) {
                    $ticket = $model->ticketRequest;
                    if ($ticket) {
                        $ticket->refresh();
                        \App\Helpers\SlaHelper::manageTicketRequestSla($ticket, false);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('TicketUpdate: Failed to update SLA clock on creation', [
                    'update_id' => $model->id,
                    'ticket_request_id' => $model->ticket_request_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function ticketRequest()
    {
        return $this->belongsTo(TicketRequest::class, 'ticket_request_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_update_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_update_id')->orderBy('created_at', 'asc');
    }
}
