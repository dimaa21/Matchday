<?php

use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\TournamentController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [TokenController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [TokenController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/user', fn () => request()->user())->middleware('auth:sanctum');

Route::get('/tournaments/{tournament}/standings', [TournamentController::class, 'standings']);
Route::get('/tournaments/{tournament}/fixtures', [TournamentController::class, 'fixtures']);
Route::get('/tournaments/{tournament}/top-scorers', [TournamentController::class, 'scorers']);
Route::get('/matches/{match}', [TournamentController::class, 'match']);
