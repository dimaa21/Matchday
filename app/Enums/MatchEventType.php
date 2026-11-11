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
            self::Goal => 'Гол',
            self::OwnGoal => 'Автогол',
            self::Penalty => 'Пенальті',
            self::Yellow => 'Жовта картка',
            self::Red => 'Червона картка',
            self::Substitution => 'Заміна',
        };
    }

    public function scores(): bool
    {
        return in_array($this, [self::Goal, self::OwnGoal, self::Penalty], true);
    }
}
