<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\SaveSquadPlayerRequest;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SquadController extends Controller
{
    public function show(): Response
    {
        $team = auth()->user()->team;

        return Inertia::render('Captain/Squad', [
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'city' => $team->city,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'initials' => $team->initials(),
            ] : null,
            'players' => $team
                ? $team->players->map(fn (Player $player) => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'number' => $player->squad_number,
                    'position' => $player->position->value,
                    'position_label' => $player->position->label(),
                    'photo_url' => $player->photo_url,
                ])
                : [],
        ]);
    }

    public function store(SaveSquadPlayerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        $data['team_id'] = $request->user()->team_id;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        Player::query()->create($data);

        return back()->with('success', 'Player added to the squad.');
    }

    public function update(SaveSquadPlayerRequest $request, Player $player): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($player->photo && ! str_starts_with($player->photo, 'http')) {
                Storage::disk('public')->delete($player->photo);
            }

            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        return back()->with('success', 'Squad updated.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        $player->delete();

        return back()->with('success', 'Player removed from the squad.');
    }
}
