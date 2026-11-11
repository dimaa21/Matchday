<?php

namespace App\Models;

use App\Enums\MatchEventType;
use Database\Factories\MatchEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    /** @use HasFactory<MatchEventFactory> */
    use HasFactory;

    protected $fillable = [
        'match_id',
        'player_id',
        'type',
        'minute',
        'assist_player_id',
        'secondary_player_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => MatchEventType::class,
            'minute' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function assist(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'assist_player_id');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'secondary_player_id');
    }
}
