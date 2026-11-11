<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'round',
        'bracket_slot',
        'group_name',
        'home_team_id',
        'away_team_id',
        'referee_id',
        'kickoff_at',
        'venue',
        'status',
        'home_score',
        'away_score',
        'is_bye',
        'next_match_id',
        'next_slot',
    ];

    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'status' => MatchStatus::class,
            'home_score' => 'integer',
            'away_score' => 'integer',
            'is_bye' => 'boolean',
            'round' => 'integer',
            'bracket_slot' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id')->orderBy('minute')->orderBy('id');
    }

    public function squads(): HasMany
    {
        return $this->hasMany(MatchSquad::class, 'match_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'next_match_id');
    }

    public function involves(int $teamId): bool
    {
        return $this->home_team_id === $teamId || $this->away_team_id === $teamId;
    }
}
