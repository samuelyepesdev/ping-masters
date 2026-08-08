<?php

use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\MatchResultController;
use App\Http\Controllers\Public\TournamentController as PublicTournamentController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentRegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('torneos', [PublicTournamentController::class, 'index'])->name('public.tournaments.index');
Route::get('torneos/{tournament:slug}', [PublicTournamentController::class, 'show'])->name('public.tournaments.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('torneos/{tournament:slug}/inscribirse', [PublicTournamentController::class, 'register'])->name('public.tournaments.register');
    Route::post('torneos/{tournament:slug}/inscribirse', [PublicTournamentController::class, 'store'])->name('public.tournaments.store');

    Route::resource('tournaments', TournamentController::class);
    Route::patch('tournaments/{tournament}/registrations/{registration}', [TournamentRegistrationController::class, 'update'])
        ->name('tournaments.registrations.update');

    Route::get('tournaments/{tournament}/divisions/{division}', [DivisionController::class, 'show'])
        ->name('tournaments.divisions.show');
    Route::post('tournaments/{tournament}/divisions/{division}/draw', [DrawController::class, 'store'])
        ->name('tournaments.divisions.draw');
    Route::post('tournaments/{tournament}/divisions/{division}/swiss-next-round', [DrawController::class, 'nextSwissRound'])
        ->name('tournaments.divisions.swiss-next-round');
    Route::patch('tournaments/{tournament}/divisions/{division}/matches/{match}/result', [MatchResultController::class, 'update'])
        ->name('tournaments.divisions.matches.result');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
