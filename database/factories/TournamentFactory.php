<?php

namespace Database\Factories;

use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Ліга '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug(Str::transliterate($name)).'-'.fake()->unique()->numerify('###'),
            'season' => '2026/27',
            'format' => TournamentFormat::League,
            'status' => TournamentStatus::Active,
            'points_for_win' => 3,
            'points_for_draw' => 1,
            'legs' => 1,
        ];
    }
}
