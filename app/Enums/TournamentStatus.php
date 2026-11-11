<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Чернетка',
            self::Active => 'Триває',
            self::Finished => 'Завершено',
        };
    }
}
