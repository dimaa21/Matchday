<?php

namespace App\Events;

use App\Models\Game;
use App\Services\MatchPayload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('matches.'.$this->game->id)];
    }

    public function broadcastAs(): string
    {
        return 'match.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return app(MatchPayload::class)->broadcast($this->game);
    }
}
