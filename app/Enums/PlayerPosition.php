<?php

namespace App\Enums;

enum PlayerPosition: string
{
    case Goalkeeper = 'gk';
    case Defender = 'df';
    case Midfielder = 'mf';
    case Forward = 'fw';

    public function label(): string
    {
        return match ($this) {
            self::Goalkeeper => 'Воротар',
            self::Defender => 'Захисник',
            self::Midfielder => 'Півзахисник',
            self::Forward => 'Нападник',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Goalkeeper => 'ВР',
            self::Defender => 'ЗХ',
            self::Midfielder => 'ПЗ',
            self::Forward => 'НП',
        };
    }
}
