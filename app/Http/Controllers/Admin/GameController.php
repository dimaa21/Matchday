<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchStatus;
use App\Enums\UserRole;
use App\Events\MatchFinished;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGameRequest;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\GamePresenter;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(GamePresenter $games): Response
    {
        $this->authorize('create', Game::class);

        $matches = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->latest('kickoff_at')
            ->paginate(20)
            ->through(fn (Game $game) => $games->summary($game));

        return Inertia::render('Admin/Matches/Index', [
            'matches' => $matches,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Game::class);

        return Inertia::render('Admin/Matches/Form', [
            'match' => null,
            ...$this->options(),
        ]);
    }

    public function store(SaveGameRequest $request, StandingsService $standings): RedirectResponse
    {
        $game = Game::query()->create($request->validated());
        $this->refreshIfFinished($game, $standings);

        return redirect()->route('admin.matches.index')->with('success', 'Матч створено.');
    }

    public function edit(Game $match): Response
    {
        $this->authorize('update', $match);

        return Inertia::render('Admin/Matches/Form', [
            'match' => [
                'id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'round' => $match->round,
                'group_name' => $match->group_name,
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'referee_id' => $match->referee_id,
                'kickoff_at' => $match->kickoff_at?->format('Y-m-d\TH:i'),
                'venue' => $match->venue,
                'status' => $match->status->value,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'is_bye' => $match->is_bye,
            ],
            ...$this->options(),
        ]);
    }

    public function update(SaveGameRequest $request, Game $match, StandingsService $standings): RedirectResponse
    {
        $match->update($request->validated());
        $this->refreshIfFinished($match->fresh(), $standings);

        return redirect()->route('admin.matches.index')->with('success', 'Матч оновлено.');
    }

    public function destroy(Game $match, StandingsService $standings): RedirectResponse
    {
        $this->authorize('delete', $match);
        $tournament = $match->tournament;
        $match->delete();

        if ($tournament) {
            $standings->invalidate($tournament);
        }

        return redirect()->route('admin.matches.index')->with('success', 'Матч видалено.');
    }

    private function refreshIfFinished(Game $game, StandingsService $standings): void
    {
        if ($game->status !== MatchStatus::Finished) {
            return;
        }

        $standings->invalidate($game->tournament);
        event(new MatchFinished($game));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'tournaments' => Tournament::query()->orderBy('name')->get(['id', 'name']),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
            'referees' => User::query()->where('role', UserRole::Referee)->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(MatchStatus::cases())->map(fn (MatchStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ];
    }
}
