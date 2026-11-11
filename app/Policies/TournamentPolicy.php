<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Tournament $tournament): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->isAdmin();
    }

    public function generateFixtures(User $user, Tournament $tournament): bool
    {
        return $user->isAdmin();
    }
}
