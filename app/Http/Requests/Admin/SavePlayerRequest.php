<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlayerPosition;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');

        return $player instanceof Player
            ? $this->user()->can('update', $player)
            : $this->user()->can('create', Player::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $player = $this->route('player');
        $teamId = $this->input('team_id', $player?->team_id);

        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:120'],
            'squad_number' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                Rule::unique('players', 'squad_number')->where('team_id', $teamId)->ignore($player?->id),
            ],
            'position' => ['required', Rule::enum(PlayerPosition::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
