<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Заплановано',
            self::Live => 'Наживо',
            self::Finished => 'Завершено',
        };
    }
}
