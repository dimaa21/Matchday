<?php

namespace App\Http\Controllers\Public;

use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use App\Services\GamePresenter;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(GamePresenter $games): Response
    {
        $tournaments = Tournament::query()
            ->where('status', '!=', TournamentStatus::Draft)
            ->withCount('teams')
            ->orderBy('name')
            ->get();

        $fixtures = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->where('is_bye', false)
            ->whereIn('tournament_id', $tournaments->pluck('id'))
            ->get();

        $sections = $tournaments->map(function (Tournament $tournament) use ($fixtures, $games) {
            $rows = $fixtures->where('tournament_id', $tournament->id)->values();
            $focus = $this->focusRound($rows);

            $visible = $rows
                ->filter(fn (Game $game) => $focus === null || abs($game->round - $focus) <= 1)
                ->sortBy(fn (Game $game) => sprintf('%03d-%010d', $game->round, $game->kickoff_at?->timestamp ?? 0))
                ->groupBy('round');

            return [
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'season' => $tournament->season,
                'rounds' => $visible->map(fn (Collection $matches, $round) => [
                    'round' => (int) $round,
                    'matches' => $matches->map(fn (Game $game) => $games->summary($game))->values(),
                ])->values(),
            ];
        })->filter(fn (array $section) => $section['rounds']->isNotEmpty())->values();

        return Inertia::render('Home', [
            'sections' => $sections,
            'tournaments' => $tournaments->map(fn (Tournament $tournament) => [
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'season' => $tournament->season,
                'format' => $tournament->format->label(),
                'status_label' => $tournament->status->label(),
                'teams_count' => $tournament->teams_count,
            ]),
        ]);
    }

    /**
     * @param  Collection<int, Game>  $games
     */
    private function focusRound(Collection $games): ?int
    {
        $live = $games->first(fn (Game $game) => $game->status === MatchStatus::Live);

        if ($live !== null) {
            return $live->round;
        }

        $closest = $games
            ->filter(fn (Game $game) => $game->kickoff_at !== null)
            ->sortBy(fn (Game $game) => abs($game->kickoff_at->getTimestamp() - now()->getTimestamp()))
            ->first();

        return $closest?->round;
    }
}
