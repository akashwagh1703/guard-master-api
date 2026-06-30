<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasAuditColumns, SoftDeletes;

    protected $fillable = [
        'name', 'start_time', 'end_time', 'grace_minutes', 'late_after', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'status' => RecordStatus::class,
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GuardAssignment::class);
    }
}
