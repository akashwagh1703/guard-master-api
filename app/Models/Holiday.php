<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'description', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
