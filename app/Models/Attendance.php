<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'guard_id', 'site_id', 'shift_id', 'assignment_id', 'date',
        'check_in_time', 'check_out_time',
        'check_in_latitude', 'check_in_longitude',
        'check_out_latitude', 'check_out_longitude',
        'check_in_photo', 'check_out_photo',
        'working_hours', 'late_minutes', 'overtime_hours',
        'status', 'admin_override', 'remarks',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'working_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'admin_override' => 'boolean',
            'status' => AttendanceStatus::class,
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

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(GuardAssignment::class, 'assignment_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
