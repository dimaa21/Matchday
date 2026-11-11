<?php

namespace App\Enums;

enum TournamentFormat: string
{
    case League = 'league';
    case Cup = 'cup';
    case GroupsPlayoff = 'groups_playoff';

    public function label(): string
    {
        return match ($this) {
            self::League => 'League',
            self::Cup => 'Cup',
            self::GroupsPlayoff => 'Groups + playoff',
        };
    }
}
