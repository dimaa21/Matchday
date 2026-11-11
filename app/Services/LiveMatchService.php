<?php

namespace App\Services;

use App\Enums\MatchEventType;
use App\Enums\MatchStatus;
use App\Events\MatchFinished;
use App\Events\MatchUpdated;
use App\Models\Game;
use App\Models\MatchEvent;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiveMatchService
{
    public function __construct(
        private readonly SuspensionService $suspensions,
        private readonly StandingsService $standings,
    ) {}

    public function start(Game $game): Game
    {
        if ($game->status !== MatchStatus::Scheduled || $game->is_bye) {
            throw ValidationException::withMessages([
                'match' => 'Почати можна лише запланований матч.',
            ]);
        }

        $game->update(['status' => MatchStatus::Live]);
        $game->refresh();
        event(new MatchUpdated($game));

        return $game;
    }

    public function finish(Game $game): Game
    {
        if ($game->status !== MatchStatus::Live) {
            throw ValidationException::withMessages([
                'match' => 'Завершити можна лише матч, який іде просто зараз.',
            ]);
        }

        $game->update(['status' => MatchStatus::Finished]);
        $game->refresh();
        $this->standings->invalidate($game->tournament);
        event(new MatchUpdated($game));
        event(new MatchFinished($game));

        return $game;
    }

    /**
     * @param  array{type: string, player_id: int, minute: int, assist_player_id?: ?int, secondary_player_id?: ?int}  $data
     */
    public function record(Game $game, array $data): MatchEvent
    {
        if ($game->status !== MatchStatus::Live) {
            throw ValidationException::withMessages([
                'match' => 'Події можна додавати лише під час живого матчу.',
            ]);
        }

        $type = $data['type'] instanceof MatchEventType ? $data['type'] : MatchEventType::from($data['type']);
        $player = Player::query()->findOrFail($data['player_id']);
        $this->assertParticipant($game, $player);

        if ($this->suspensions->isSuspended($player, $game)) {
            throw ValidationException::withMessages([
                'player_id' => 'Гравець дискваліфікований на цей матч.',
            ]);
        }

        $assistId = $data['assist_player_id'] ?? null;
        $secondaryId = $data['secondary_player_id'] ?? null;

        if ($type === MatchEventType::Substitution) {
            if ($secondaryId === null) {
                throw ValidationException::withMessages([
                    'secondary_player_id' => 'Оберіть гравця, який виходить на поле.',
                ]);
            }

            $incoming = Player::query()->findOrFail($secondaryId);
            $this->assertParticipant($game, $incoming);

            if ($incoming->team_id !== $player->team_id) {
                throw ValidationException::withMessages([
                    'secondary_player_id' => 'Заміна має бути з тієї самої команди.',
                ]);
            }

            if ($this->suspensions->isSuspended($incoming, $game)) {
                throw ValidationException::withMessages([
                    'secondary_player_id' => 'Гравець, що виходить, дискваліфікований.',
                ]);
            }
        } else {
            $secondaryId = null;
        }

        if (! in_array($type, [MatchEventType::Goal, MatchEventType::Penalty], true)) {
            $assistId = null;
        }

        if ($assistId !== null) {
            $assist = Player::query()->findOrFail($assistId);

            if ($assist->team_id !== $player->team_id) {
                throw ValidationException::withMessages([
                    'assist_player_id' => 'Асистент має бути з команди, що забила.',
                ]);
            }
        }

        $event = DB::transaction(function () use ($game, $player, $type, $data, $assistId, $secondaryId) {
            $event = $game->events()->create([
                'player_id' => $player->id,
                'type' => $type,
                'minute' => $data['minute'],
                'assist_player_id' => $assistId,
                'secondary_player_id' => $secondaryId,
            ]);

            $this->syncScore($game);

            return $event;
        });

        event(new MatchUpdated($game->refresh()));

        return $event;
    }

    public function delete(Game $game, MatchEvent $event): void
    {
        if ($event->match_id !== $game->id) {
            abort(404);
        }

        if ($game->status !== MatchStatus::Live) {
            throw ValidationException::withMessages([
                'match' => 'Видаляти події можна лише під час живого матчу.',
            ]);
        }

        DB::transaction(function () use ($game, $event) {
            $event->delete();
            $this->syncScore($game);
        });

        event(new MatchUpdated($game->refresh()));
    }

    public function syncScore(Game $game): void
    {
        $home = 0;
        $away = 0;

        $events = $game->events()->with('player')->get();

        foreach ($events as $event) {
            if (! $event->type->scores() || $event->player === null) {
                continue;
            }

            $forHome = $event->player->team_id === $game->home_team_id;

            if ($event->type === MatchEventType::OwnGoal) {
                $forHome = ! $forHome;
            }

            if ($event->player->team_id !== $game->home_team_id && $event->player->team_id !== $game->away_team_id) {
                continue;
            }

            $forHome ? $home++ : $away++;
        }

        $game->update([
            'home_score' => $home,
            'away_score' => $away,
        ]);
    }

    private function assertParticipant(Game $game, Player $player): void
    {
        if (! in_array($player->team_id, [$game->home_team_id, $game->away_team_id], true)) {
            throw ValidationException::withMessages([
                'player_id' => 'Гравець не бере участі в цьому матчі.',
            ]);
        }
    }
}
