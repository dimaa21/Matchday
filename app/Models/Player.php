<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'photo',
        'squad_number',
        'position',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'squad_number' => 'integer',
            'position' => PlayerPosition::class,
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo)) {
            return null;
        }

        if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
            return $this->photo;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }

    public function squads(): HasMany
    {
        return $this->hasMany(MatchSquad::class);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];

        return mb_strtoupper(collect($parts)->take(2)->map(fn (string $part) => mb_substr($part, 0, 1))->implode(''));
    }
}
