<?php

namespace App\Http\Requests\Admin;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            ? $this->user()->can('update', $team)
            : $this->user()->can('create', Team::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teamId = $this->route('team')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('teams', 'slug')->ignore($teamId)],
            'city' => ['required', 'string', 'max:80'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
