<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'primary_color',
        'secondary_color',
        'city',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team): void {
            if (blank($team->slug)) {
                $team->slug = Str::slug(Str::transliterate($team->name));
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo)) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class)->orderBy('squad_number');
    }

    public function captain(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_team')->withPivot('group_name')->withTimestamps();
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];

        return mb_strtoupper(collect($parts)->take(2)->map(fn (string $part) => mb_substr($part, 0, 1))->implode(''));
    }
}
