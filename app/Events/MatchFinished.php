<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFinished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Game $game) {}
}
