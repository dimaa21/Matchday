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
            self::Goalkeeper => 'Goalkeeper',
            self::Defender => 'Defender',
            self::Midfielder => 'Midfielder',
            self::Forward => 'Forward',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Goalkeeper => 'GK',
            self::Defender => 'DF',
            self::Midfielder => 'MF',
            self::Forward => 'FW',
        };
    }
}
