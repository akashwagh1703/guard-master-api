<?php

namespace App\Models;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'site_id', 'guard_id', 'category', 'title', 'description',
        'priority', 'status', 'images', 'latitude', 'longitude',
        'incident_time', 'admin_comments',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'incident_time' => 'datetime',
            'priority' => IncidentPriority::class,
            'status' => IncidentStatus::class,
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
