<?php

namespace App\Http\Controllers\Referee;

use App\Enums\MatchEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referee\StoreMatchEventRequest;
use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Services\GamePresenter;
use App\Services\LiveMatchService;
use App\Services\SuspensionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsoleController extends Controller
{
    public function index(): Response
    {
        $games = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->where('is_bye', false)
            ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('referee_id', auth()->id()))
            ->orderByRaw("case status when 'live' then 0 when 'scheduled' then 1 else 2 end")
            ->orderBy('kickoff_at')
            ->limit(40)
            ->get();

        return Inertia::render('Referee/Index', [
            'matches' => $games->map(fn (Game $game) => [
                'id' => $game->id,
                'tournament' => $game->tournament?->name,
                'round' => $game->round,
                'status' => $game->status->value,
                'status_label' => $game->status->label(),
                'kickoff_label' => $game->kickoff_at?->format('d.m H:i'),
                'home' => $game->homeTeam?->name,
                'away' => $game->awayTeam?->name,
                'home_score' => $game->home_score,
                'away_score' => $game->away_score,
                'can_officiate' => auth()->user()->can('officiate', $game),
            ]),
        ]);
    }

    public function show(Game $match, GamePresenter $games, SuspensionService $suspensions): Response
    {
        $this->authorize('officiate', $match);

        $teamIds = array_filter([$match->home_team_id, $match->away_team_id]);
        $banned = $suspensions->bannedPlayerIds($match);
        $players = Player::query()
            ->whereIn('team_id', $teamIds)
            ->orderBy('squad_number')
            ->get()
            ->map(fn (Player $player) => [
                'id' => $player->id,
                'name' => $player->name,
                'number' => $player->squad_number,
                'team_id' => $player->team_id,
                'position' => $player->position->short(),
                'suspended' => in_array($player->id, $banned, true),
            ]);

        return Inertia::render('Referee/Console', [
            'match' => $games->detail($match),
            'players' => $players,
            'eventTypes' => collect(MatchEventType::cases())->map(fn (MatchEventType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function start(Game $match, LiveMatchService $live): RedirectResponse
    {
        $this->authorize('officiate', $match);
        $live->start($match);

        return back()->with('success', 'Матч розпочато.');
    }

    public function finish(Game $match, LiveMatchService $live): RedirectResponse
    {
        $this->authorize('officiate', $match);
        $live->finish($match);

        return back()->with('success', 'Матч завершено.');
    }

    public function storeEvent(StoreMatchEventRequest $request, Game $match, LiveMatchService $live): RedirectResponse
    {
        $live->record($match, $request->validated());

        return back()->with('success', 'Подію додано.');
    }

    public function destroyEvent(Game $match, MatchEvent $event, LiveMatchService $live): RedirectResponse
    {
        $this->authorize('officiate', $match);
        $live->delete($match, $event);

        return back()->with('success', 'Подію скасовано.');
    }
}
