<?php

namespace App\Enums;

enum MatchEventType: string
{
    case Goal = 'goal';
    case OwnGoal = 'own_goal';
    case Penalty = 'penalty';
    case Yellow = 'yellow';
    case Red = 'red';
    case Substitution = 'substitution';

    public function label(): string
    {
        return match ($this) {
            self::Goal => 'Goal',
            self::OwnGoal => 'Own goal',
            self::Penalty => 'Penalty',
            self::Yellow => 'Yellow card',
            self::Red => 'Red card',
            self::Substitution => 'Substitution',
        };
    }

    public function scores(): bool
    {
        return in_array($this, [self::Goal, self::OwnGoal, self::Penalty], true);
    }
}
