<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasAuditColumns, HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'client_name', 'contact_person', 'phone', 'address',
        'latitude', 'longitude', 'attendance_radius', 'status',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'status' => RecordStatus::class,
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GuardAssignment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(VisitorEntry::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
