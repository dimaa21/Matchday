<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

class PlayerPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Player $player): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || ($user->isCaptain() && $user->team_id !== null);
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isAdmin() || ($user->isCaptain() && $user->team_id === $player->team_id);
    }

    public function delete(User $user, Player $player): bool
    {
        return $user->isAdmin() || ($user->isCaptain() && $user->team_id === $player->team_id);
    }
}
