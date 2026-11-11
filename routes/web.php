<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Admin\TournamentController as AdminTournamentController;
use App\Http\Controllers\Captain\SquadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\GameController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PlayerController;
use App\Http\Controllers\Public\TeamController;
use App\Http\Controllers\Public\TournamentController;
use App\Http\Controllers\Referee\ConsoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
Route::get('/matches/{match}', [GameController::class, 'show'])->name('matches.show');
Route::get('/teams/{team:slug}', [TeamController::class, 'show'])->name('teams.show');
Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');

Route::get('/dashboard', function () {
    return redirect(auth()->user()->homeRoute());
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('tournaments/{tournament}/fixtures', [AdminTournamentController::class, 'fixtures'])->name('tournaments.fixtures');
    Route::post('tournaments/{tournament}/playoff', [AdminTournamentController::class, 'playoff'])->name('tournaments.playoff');
    Route::resource('tournaments', AdminTournamentController::class)->except(['show']);
    Route::resource('teams', AdminTeamController::class)->except(['show']);
    Route::resource('players', AdminPlayerController::class)->except(['show']);
    Route::resource('matches', AdminGameController::class)->except(['show'])->parameters(['matches' => 'match']);
});

Route::middleware(['auth', 'role:referee,admin'])->prefix('referee')->name('referee.')->group(function () {
    Route::get('/matches', [ConsoleController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [ConsoleController::class, 'show'])->name('matches.show');
    Route::post('/matches/{match}/start', [ConsoleController::class, 'start'])->name('matches.start');
    Route::post('/matches/{match}/finish', [ConsoleController::class, 'finish'])->name('matches.finish');
    Route::post('/matches/{match}/events', [ConsoleController::class, 'storeEvent'])->name('matches.events.store');
    Route::delete('/matches/{match}/events/{event}', [ConsoleController::class, 'destroyEvent'])->name('matches.events.destroy');
});

Route::middleware(['auth', 'role:captain'])->prefix('captain')->name('captain.')->group(function () {
    Route::get('/squad', [SquadController::class, 'show'])->name('squad');
    Route::post('/players', [SquadController::class, 'store'])->name('players.store');
    Route::patch('/players/{player}', [SquadController::class, 'update'])->name('players.update');
    Route::delete('/players/{player}', [SquadController::class, 'destroy'])->name('players.destroy');
});

require __DIR__.'/auth.php';
