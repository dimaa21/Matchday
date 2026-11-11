<?php

namespace App\Http\Requests\Admin;

use App\Enums\MatchStatus;
use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('match');

        return $game instanceof Game
            ? $this->user()->can('update', $game)
            : $this->user()->can('create', Game::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'round' => ['required', 'integer', 'min:1', 'max:64'],
            'group_name' => ['nullable', 'string', 'max:8'],
            'home_team_id' => ['nullable', 'integer', 'exists:teams,id', 'different:away_team_id'],
            'away_team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'referee_id' => ['nullable', 'integer', 'exists:users,id'],
            'kickoff_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::enum(MatchStatus::class)],
            'home_score' => ['required', 'integer', 'min:0', 'max:30'],
            'away_score' => ['required', 'integer', 'min:0', 'max:30'],
            'is_bye' => ['sometimes', 'boolean'],
        ];
    }
}
