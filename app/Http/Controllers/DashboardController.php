<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $data = [];

        if ($user->isSuperAdmin()) {
            $data['admin'] = $this->adminStats();
        }

        if ($user->isOrganizer() || $user->isSuperAdmin()) {
            $data['organizer'] = $this->organizerStats($user);
        }

        if ($user->isReferee()) {
            $data['referee'] = $this->refereeStats($user);
        }

        if ($user->player) {
            $data['player'] = $this->playerStats($user);
        }

        return Inertia::render('dashboard', $data);
    }

    private function adminStats(): array
    {
        return [
            'tournaments_total' => Tournament::count(),
            'tournaments_active' => Tournament::whereIn('status', ['registration_open', 'registration_closed', 'in_progress'])->count(),
            'users_total' => User::count(),
            'matches_played' => TournamentMatch::where('status', 'completed')->count(),
            'recent_tournaments' => Tournament::withCount(['divisions', 'registrations'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'slug', 'status', 'start_date', 'end_date']),
        ];
    }

    private function organizerStats(User $user): array
    {
        $query = Tournament::query();

        if (! $user->isSuperAdmin()) {
            $query->where('created_by', $user->id);
        }

        $tournamentIds = (clone $query)->pluck('id');

        return [
            'tournaments_total' => $tournamentIds->count(),
            'tournaments_open' => (clone $query)->where('status', 'registration_open')->count(),
            'pending_registrations' => TournamentRegistration::whereIn('tournament_id', $tournamentIds)
                ->where('status', 'pending')
                ->count(),
            'upcoming_tournaments' => (clone $query)
                ->whereIn('status', ['registration_open', 'registration_closed', 'in_progress'])
                ->withCount(['divisions', 'registrations'])
                ->orderBy('start_date')
                ->limit(5)
                ->get(['id', 'name', 'slug', 'status', 'start_date', 'end_date', 'created_by']),
        ];
    }

    private function refereeStats(User $user): array
    {
        $query = TournamentMatch::where('referee_id', $user->id);

        return [
            'upcoming_count' => (clone $query)->whereIn('status', ['ready', 'in_progress'])->count(),
            'upcoming_matches' => (clone $query)
                ->whereIn('status', ['ready', 'in_progress'])
                ->with(['entrant1.registration.player.user', 'entrant2.registration.player.user', 'division.tournament'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
                ->map(fn (TournamentMatch $match) => [
                    'id' => $match->id,
                    'tournament_id' => $match->division->tournament_id,
                    'division_id' => $match->tournament_division_id,
                    'tournament_name' => $match->division->tournament->name,
                    'division_name' => $match->division->name,
                    'entrant1_name' => $match->entrant1?->registration?->player?->user?->name ?? 'Por definir',
                    'entrant2_name' => $match->entrant2?->registration?->player?->user?->name ?? 'Por definir',
                ]),
        ];
    }

    private function playerStats(User $user): array
    {
        $player = $user->player;

        $upcomingRegistrations = TournamentRegistration::where('player_id', $player->id)
            ->whereHas('tournament', fn ($q) => $q->whereIn('status', ['registration_open', 'registration_closed', 'in_progress']))
            ->with('tournament')
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get();

        return [
            'rating_current' => $player->rating_current,
            'level' => $player->level,
            'xp_total' => $player->xp_total,
            'matches_played' => $player->matches_played,
            'matches_won' => $player->matches_won,
            'is_elite' => $player->is_elite,
            'player_id' => $player->id,
            'upcoming_registrations' => $upcomingRegistrations,
        ];
    }
}
