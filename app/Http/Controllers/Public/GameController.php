<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\GamePresenter;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function show(Game $match, GamePresenter $games): Response
    {
        return Inertia::render('Matches/Show', [
            'match' => $games->detail($match),
        ]);
    }
}
