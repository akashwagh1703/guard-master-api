<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Guard = 'guard';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Guard => 'Security Guard',
        };
    }
}
