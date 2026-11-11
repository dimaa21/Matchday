<?php

namespace App\Models;

use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'season',
        'format',
        'status',
        'points_for_win',
        'points_for_draw',
        'legs',
    ];

    protected function casts(): array
    {
        return [
            'format' => TournamentFormat::class,
            'status' => TournamentStatus::class,
            'points_for_win' => 'integer',
            'points_for_draw' => 'integer',
            'legs' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tournament $tournament): void {
            if (blank($tournament->slug)) {
                $tournament->slug = Str::slug(Str::transliterate($tournament->name));
            }
        });
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_team')
            ->withPivot('group_name')
            ->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
