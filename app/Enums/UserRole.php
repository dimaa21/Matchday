<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Referee = 'referee';
    case Captain = 'captain';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Referee => 'Referee',
            self::Captain => 'Captain',
        };
    }
}
