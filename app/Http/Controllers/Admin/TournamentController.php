<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTournamentRequest;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\FixtureScheduler;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', Tournament::class);

        $tournaments = Tournament::query()->withCount(['teams', 'games'])->latest()->paginate(12)->through(fn (Tournament $tournament) => [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'slug' => $tournament->slug,
            'season' => $tournament->season,
            'format' => $tournament->format->value,
            'format_label' => $tournament->format->label(),
            'status' => $tournament->status->value,
            'status_label' => $tournament->status->label(),
            'teams_count' => $tournament->teams_count,
            'games_count' => $tournament->games_count,
        ]);

        return Inertia::render('Admin/Tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tournament::class);

        return Inertia::render('Admin/Tournaments/Form', [
            'tournament' => null,
            'teams' => $this->teamOptions(),
        ]);
    }

    public function store(SaveTournamentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tournament = Tournament::query()->create([
            ...collect($data)->except('teams')->all(),
            'slug' => $this->uniqueSlug($data['name']),
        ]);
        $this->syncTeams($tournament, $data['teams'] ?? []);

        return redirect()->route('admin.tournaments.index')->with('success', 'Турнір створено.');
    }

    public function edit(Tournament $tournament): Response
    {
        $this->authorize('update', $tournament);

        return Inertia::render('Admin/Tournaments/Form', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'slug' => $tournament->slug,
                'season' => $tournament->season,
                'format' => $tournament->format->value,
                'status' => $tournament->status->value,
                'points_for_win' => $tournament->points_for_win,
                'points_for_draw' => $tournament->points_for_draw,
                'legs' => $tournament->legs,
                'teams' => $tournament->teams->map(fn (Team $team) => [
                    'id' => $team->id,
                    'group_name' => $team->pivot->group_name,
                ]),
            ],
            'teams' => $this->teamOptions(),
        ]);
    }

    public function update(SaveTournamentRequest $request, Tournament $tournament, StandingsService $standings): RedirectResponse
    {
        $data = $request->validated();
        $tournament->update(collect($data)->except('teams')->all());
        $this->syncTeams($tournament, $data['teams'] ?? []);
        $standings->invalidate($tournament);

        return redirect()->route('admin.tournaments.index')->with('success', 'Турнір оновлено.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $this->authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('admin.tournaments.index')->with('success', 'Турнір видалено.');
    }

    public function fixtures(Tournament $tournament, FixtureScheduler $scheduler): RedirectResponse
    {
        $this->authorize('generateFixtures', $tournament);
        $count = $scheduler->generate($tournament);

        return back()->with('success', "Календар створено: {$count} матчів.");
    }

    public function playoff(Tournament $tournament, FixtureScheduler $scheduler): RedirectResponse
    {
        $this->authorize('generateFixtures', $tournament);
        $count = $scheduler->generatePlayoff($tournament);

        return back()->with('success', "Плей-оф створено: {$count} матчів.");
    }

    /**
     * @param  list<array{id: int, group_name?: ?string}>  $teams
     */
    private function syncTeams(Tournament $tournament, array $teams): void
    {
        $sync = [];

        foreach ($teams as $team) {
            $sync[$team['id']] = ['group_name' => $team['group_name'] ?: null];
        }

        $tournament->teams()->sync($sync);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(Str::transliterate($name)) ?: 'tournament';
        $slug = $base;
        $suffix = 2;

        while (Tournament::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return list<array{id: int, name: string, city: string}>
     */
    private function teamOptions(): array
    {
        return Team::query()->orderBy('name')->get(['id', 'name', 'city'])->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'city' => $team->city,
        ])->all();
    }
}
