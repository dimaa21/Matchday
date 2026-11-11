<?php

namespace App\Http\Middleware;

use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'role_label' => $user->role?->label(),
                    'team_id' => $user->team_id,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'competitions' => fn () => Tournament::query()
                ->where('status', '!=', TournamentStatus::Draft)
                ->orderBy('name')
                ->get(['name', 'slug', 'season'])
                ->map(fn (Tournament $tournament) => [
                    'name' => $tournament->name,
                    'slug' => $tournament->slug,
                    'season' => $tournament->season,
                ]),
        ];
    }
}
