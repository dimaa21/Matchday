<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePlayerRequest;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', Player::class);

        $players = Player::query()->with('team')->orderBy('name')->paginate(25)->through(fn (Player $player) => [
            'id' => $player->id,
            'name' => $player->name,
            'number' => $player->squad_number,
            'position' => $player->position->label(),
            'team' => $player->team?->name,
            'team_color' => $player->team?->primary_color,
        ]);

        return Inertia::render('Admin/Players/Index', [
            'players' => $players,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Player::class);

        return Inertia::render('Admin/Players/Form', [
            'player' => null,
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(SavePlayerRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        Player::query()->create($data);

        return redirect()->route('admin.players.index')->with('success', 'Player added.');
    }

    public function edit(Player $player): Response
    {
        $this->authorize('update', $player);

        return Inertia::render('Admin/Players/Form', [
            'player' => [
                'id' => $player->id,
                'team_id' => $player->team_id,
                'name' => $player->name,
                'squad_number' => $player->squad_number,
                'position' => $player->position->value,
                'photo_url' => $player->photo_url,
            ],
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(SavePlayerRequest $request, Player $player): RedirectResponse
    {
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($player->photo && ! str_starts_with($player->photo, 'http')) {
                Storage::disk('public')->delete($player->photo);
            }

            $data['photo'] = $request->file('photo')->store('players', 'public');
        }

        $player->update($data);

        return redirect()->route('admin.players.index')->with('success', 'Player updated.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        $player->delete();

        return redirect()->route('admin.players.index')->with('success', 'Player deleted.');
    }
}
