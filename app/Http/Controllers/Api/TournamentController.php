<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\GamePresenter;
use App\Services\StandingsService;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function standings(Tournament $tournament, StandingsService $standings): JsonResponse
    {
        return response()->json([
            'tournament' => $this->meta($tournament),
            'tables' => $standings->cached($tournament),
        ]);
    }

    public function fixtures(Request $request, Tournament $tournament, GamePresenter $games): JsonResponse
    {
        $round = $request->filled('round') ? $request->integer('round') : null;

        $fixtures = $tournament->games()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->when($round, fn ($query) => $query->where('round', $round))
            ->orderBy('round')
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Game $game) => $games->summary($game));

        return response()->json([
            'tournament' => $this->meta($tournament),
            'round' => $round,
            'rounds' => $tournament->games()->select('round')->distinct()->orderBy('round')->pluck('round'),
            'fixtures' => $fixtures,
        ]);
    }

    public function scorers(Tournament $tournament, StatisticsService $statistics): JsonResponse
    {
        return response()->json([
            'tournament' => $this->meta($tournament),
            'scorers' => $statistics->topScorers($tournament, 20),
        ]);
    }

    public function match(Game $match, GamePresenter $games): JsonResponse
    {
        return response()->json($games->detail($match));
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(Tournament $tournament): array
    {
        return [
            'name' => $tournament->name,
            'slug' => $tournament->slug,
            'season' => $tournament->season,
            'format' => $tournament->format->value,
            'status' => $tournament->status->value,
        ];
    }
}
