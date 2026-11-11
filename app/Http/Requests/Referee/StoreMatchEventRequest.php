<?php

namespace App\Http\Requests\Referee;

use App\Enums\MatchEventType;
use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('match');

        return $game instanceof Game && $this->user()->can('officiate', $game);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(MatchEventType::class)],
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'minute' => ['required', 'integer', 'min:1', 'max:120'],
            'assist_player_id' => ['nullable', 'integer', 'exists:players,id', 'different:player_id'],
            'secondary_player_id' => ['nullable', 'integer', 'exists:players,id', 'different:player_id'],
        ];
    }
}
