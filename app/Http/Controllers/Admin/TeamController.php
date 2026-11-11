<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Admin/Teams/Index', [
            'teams' => Team::query()->withCount('players')->orderBy('name')->paginate(20)->through(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'city' => $team->city,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'initials' => $team->initials(),
                'players_count' => $team->players_count,
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Admin/Teams/Form', ['team' => null]);
    }

    public function store(SaveTeamRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Team::query()->create($data);

        return redirect()->route('admin.teams.index')->with('success', 'Team created.');
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('Admin/Teams/Form', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'city' => $team->city,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'logo_url' => $team->logo_url,
            ],
        ]);
    }

    public function update(SaveTeamRequest $request, Team $team): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($team->logo && ! str_starts_with($team->logo, 'http')) {
                Storage::disk('public')->delete($team->logo);
            }

            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $team->update($data);

        return redirect()->route('admin.teams.index')->with('success', 'Team updated.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);
        $team->delete();

        return redirect()->route('admin.teams.index')->with('success', 'Team deleted.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(Str::transliterate($name)) ?: 'team';
        $slug = $base;
        $suffix = 2;

        while (Team::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
