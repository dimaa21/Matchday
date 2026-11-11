<?php

namespace App\Http\Requests\Captain;

use App\Enums\PlayerPosition;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSquadPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');

        if ($player instanceof Player) {
            return $this->user()->can('update', $player);
        }

        return $this->user()->can('create', Player::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $player = $this->route('player');

        return [
            'name' => ['required', 'string', 'max:120'],
            'squad_number' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                Rule::unique('players', 'squad_number')
                    ->where('team_id', $this->user()->team_id)
                    ->ignore($player?->id),
            ],
            'position' => ['required', Rule::enum(PlayerPosition::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
