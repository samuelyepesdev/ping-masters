<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CasualMatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DivisionTemplateController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchResultController;
use App\Http\Controllers\MatchScoringController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Public\MatchController as PublicMatchController;
use App\Http\Controllers\Public\PlayerController as PublicPlayerController;
use App\Http\Controllers\Public\TournamentController as PublicTournamentController;
use App\Http\Controllers\RefereeController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('torneos', [PublicTournamentController::class, 'index'])->name('public.tournaments.index');
Route::get('torneos/{tournament:slug}', [PublicTournamentController::class, 'show'])->name('public.tournaments.show');
Route::get('torneos/{tournament:slug}/inscribirse', [PublicTournamentController::class, 'register'])->name('public.tournaments.register');
Route::post('torneos/{tournament:slug}/inscribirse', [PublicTournamentController::class, 'store'])->name('public.tournaments.store');
Route::get('partidos/{match}', [PublicMatchController::class, 'show'])->name('public.matches.show');
Route::get('ranking', [PublicPlayerController::class, 'ranking'])->name('public.players.ranking');
Route::get('jugadores/{player}', [PublicPlayerController::class, 'show'])->name('public.players.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    Route::get('tournaments/{tournament}/divisions/{division}/matches/{match}/score', [MatchScoringController::class, 'show'])
        ->name('tournaments.divisions.matches.score');
    Route::post('tournaments/{tournament}/divisions/{division}/matches/{match}/start', [MatchScoringController::class, 'start'])
        ->name('tournaments.divisions.matches.start');
    Route::post('tournaments/{tournament}/divisions/{division}/matches/{match}/point', [MatchScoringController::class, 'point'])
        ->name('tournaments.divisions.matches.point');
    Route::post('tournaments/{tournament}/divisions/{division}/matches/{match}/undo', [MatchScoringController::class, 'undo'])
        ->name('tournaments.divisions.matches.undo');
    Route::post('tournaments/{tournament}/divisions/{division}/matches/{match}/walkover', [MatchScoringController::class, 'walkover'])
        ->name('tournaments.divisions.matches.walkover');
    Route::patch('tournaments/{tournament}/divisions/{division}/matches/{match}/referee', [MatchScoringController::class, 'assignReferee'])
        ->name('tournaments.divisions.matches.referee');

    Route::get('scoring', [RefereeController::class, 'index'])->name('referee.index');

    Route::get('retos', [CasualMatchController::class, 'index'])->name('games.index');
    Route::post('retos', [CasualMatchController::class, 'store'])->name('games.store');
    Route::post('retos/unirse', [CasualMatchController::class, 'join'])->name('games.join');
    Route::get('retos/{casualMatch:code}', [CasualMatchController::class, 'show'])->name('games.show');
    Route::post('retos/{casualMatch:code}/start', [CasualMatchController::class, 'start'])->name('games.start');
    Route::post('retos/{casualMatch:code}/point', [CasualMatchController::class, 'point'])->name('games.point');
    Route::post('retos/{casualMatch:code}/undo', [CasualMatchController::class, 'undo'])->name('games.undo');
    Route::post('retos/{casualMatch:code}/forfeit', [CasualMatchController::class, 'forfeit'])->name('games.forfeit');
    Route::post('retos/{casualMatch:code}/cancel', [CasualMatchController::class, 'cancel'])->name('games.cancel');

    Route::get('tournaments/{tournament}/pdf/schedule', [PdfController::class, 'schedule'])
        ->name('tournaments.pdf.schedule');
    Route::get('tournaments/{tournament}/divisions/{division}/pdf/bracket', [PdfController::class, 'bracket'])
        ->name('tournaments.divisions.pdf.bracket');
    Route::get('tournaments/{tournament}/divisions/{division}/matches/{match}/pdf/scorecard', [PdfController::class, 'scorecard'])
        ->name('tournaments.divisions.matches.pdf.scorecard');
    Route::get('tournaments/{tournament}/divisions/{division}/entrants/{entrant}/pdf/certificate', [PdfController::class, 'certificate'])
        ->name('tournaments.divisions.entrants.pdf.certificate');

    Route::get('profile/me', [PublicPlayerController::class, 'me'])->name('profile.me');

    Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('admin/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles.update');
    Route::post('admin/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset-password');
    Route::delete('admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::resource('plantillas/categorias', DivisionTemplateController::class)
        ->except(['show'])
        ->parameters(['categorias' => 'divisionTemplate'])
        ->names('templates.divisions');
    Route::resource('plantillas/formularios', FormTemplateController::class)
        ->except(['show'])
        ->parameters(['formularios' => 'formTemplate'])
        ->names('templates.forms');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
