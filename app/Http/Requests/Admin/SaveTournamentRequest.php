<?php

namespace App\Http\Requests\Admin;

use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tournament = $this->route('tournament');

        return $tournament instanceof Tournament
            ? $this->user()->can('update', $tournament)
            : $this->user()->can('create', Tournament::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'season' => ['required', 'string', 'max:20'],
            'format' => ['required', Rule::enum(TournamentFormat::class)],
            'status' => ['required', Rule::enum(TournamentStatus::class)],
            'points_for_win' => ['required', 'integer', 'min:0', 'max:10'],
            'points_for_draw' => ['required', 'integer', 'min:0', 'max:10'],
            'legs' => ['required', 'integer', 'in:1,2'],
            'teams' => ['array'],
            'teams.*.id' => ['required', 'integer', 'exists:teams,id'],
            'teams.*.group_name' => ['nullable', 'string', 'max:8'],
        ];
    }
}
