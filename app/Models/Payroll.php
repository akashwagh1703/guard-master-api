<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'guard_id', 'month', 'year', 'base_salary',
        'present_days', 'absent_days', 'half_days', 'late_count',
        'overtime_amount', 'bonus', 'advance', 'deduction', 'net_salary',
        'status', 'generated_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'advance' => 'decimal:2',
            'deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'generated_at' => 'datetime',
            'status' => PayrollStatus::class,
        ];
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
