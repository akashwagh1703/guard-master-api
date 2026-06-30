<?php

namespace App\Models;

use App\Enums\VisitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorEntry extends Model
{
    protected $fillable = [
        'site_id', 'guard_id', 'visitor_name', 'mobile', 'purpose',
        'person_to_meet', 'vehicle_number', 'id_type', 'id_number', 'photo',
        'entry_time', 'exit_time', 'remarks',
        'entry_latitude', 'entry_longitude', 'exit_latitude', 'exit_longitude',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_time' => 'datetime',
            'exit_time' => 'datetime',
            'status' => VisitorStatus::class,
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
