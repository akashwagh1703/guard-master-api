<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuardAssignment extends Model
{
    use HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'guard_id', 'site_id', 'shift_id', 'from_date', 'to_date', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'status' => RecordStatus::class,
        ];
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'assignment_id');
    }
}
