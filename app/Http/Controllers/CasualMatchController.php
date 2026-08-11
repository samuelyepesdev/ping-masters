<?php

namespace App\Http\Controllers;

use App\Events\CasualMatchScoreUpdated;
use App\Models\CasualMatch;
use App\Models\Player;
use App\Services\Ratings\EloRatingService;
use App\Services\Scoring\CasualMatchScoringService;
use App\Services\Scoring\CasualMatchStatePresenter;
use App\Services\Xp\XpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CasualMatchController extends Controller
{
    public function __construct(
        private readonly CasualMatchStatePresenter $presenter,
        private readonly EloRatingService $elo,
        private readonly XpService $xp,
    ) {}

    public function index(Request $request): Response
    {
        $player = Player::firstOrCreate(['user_id' => $request->user()->id], ['club_id' => $request->user()->club_id]);

        $matches = CasualMatch::where(function ($query) use ($player) {
            $query->where('creator_player_id', $player->id)
                ->orWhere('opponent_player_id', $player->id);
        })
            ->where('status', '!=', 'cancelled')
            ->with(['creator.user', 'opponent.user'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CasualMatch $match) => [
                'id' => $match->id,
                'code' => $match->code,
                'match_type' => $match->match_type,
                'status' => $match->status,
                'creator_name' => $match->creator->user->name,
                'opponent_name' => $match->opponent?->user->name,
                'score_summary' => $match->score_summary,
                'wager_points' => $match->wager_points,
                'is_mine_to_join' => $match->status === 'waiting' && $match->creator_player_id !== $player->id,
            ]);

        return Inertia::render('games/index', [
            'matches' => $matches,
            'pendingWager' => $request->session()->get('pendingWager'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'match_type' => 'required|in:ranked,friendly',
            'best_of' => 'required|in:5,7',
            'points_to_win' => 'required|integer|min:5|max:21',
            'wager_points' => 'nullable|integer|min:1|max:500',
        ]);

        $user = $request->user();
        $player = Player::firstOrCreate(['user_id' => $user->id], ['club_id' => $user->club_id]);

        if (! $user->hasRole('player')) {
            $user->assignRole('player');
        }

        $match = CasualMatch::create([
            'code' => $this->generateCode(),
            'match_type' => $validated['match_type'],
            'status' => 'waiting',
            'best_of' => (int) $validated['best_of'],
            'points_to_win' => (int) $validated['points_to_win'],
            // A wager only makes sense on a ranked match, since it rides on the ELO change.
            'wager_points' => $validated['match_type'] === 'ranked' ? $validated['wager_points'] ?? null : null,
            'creator_player_id' => $player->id,
        ]);

        return redirect()->route('games.show', $match->code);
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:8',
            'accept_wager' => 'nullable|boolean',
        ]);

        $match = CasualMatch::where('code', strtoupper(trim($validated['code'])))->first();

        if (! $match) {
            return back()->withErrors(['code' => 'No encontramos ningún reto con ese código.'])->withInput();
        }

        $user = $request->user();
        $player = Player::firstOrCreate(['user_id' => $user->id], ['club_id' => $user->club_id]);

        if ($match->creator_player_id === $player->id) {
            return back()->withErrors(['code' => 'No puedes unirte a tu propio reto.'])->withInput();
        }

        if ($match->status !== 'waiting') {
            return back()->withErrors(['code' => 'Este reto ya no está disponible para unirse.'])->withInput();
        }

        // Wagered retos need an explicit accept from the joining player — the code alone
        // isn't consent to the stakes.
        if ($match->wager_points && ! $request->boolean('accept_wager')) {
            return back()->with('pendingWager', ['code' => $match->code, 'wager_points' => $match->wager_points]);
        }

        if (! $user->hasRole('player')) {
            $user->assignRole('player');
        }

        $match->update([
            'opponent_player_id' => $player->id,
            'status' => 'ready',
        ]);

        return redirect()->route('games.show', $match->code);
    }

    public function show(CasualMatch $casualMatch): Response
    {
        $this->authorize('score', $casualMatch);

        return Inertia::render('games/show', [
            'match' => $this->presenter->present($casualMatch),
        ]);
    }

    public function start(Request $request, CasualMatch $casualMatch, CasualMatchScoringService $scoring): RedirectResponse
    {
        $this->authorize('score', $casualMatch);

        $validated = $request->validate([
            'first_server_entrant_id' => 'required|integer|in:'.$casualMatch->creator_player_id.','.$casualMatch->opponent_player_id,
        ]);

        try {
            $scoring->startMatch($casualMatch, (int) $validated['first_server_entrant_id']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($casualMatch->fresh());

        return back();
    }

    public function point(Request $request, CasualMatch $casualMatch, CasualMatchScoringService $scoring): RedirectResponse
    {
        $this->authorize('score', $casualMatch);

        $validated = $request->validate([
            'entrant_id' => 'required|integer|in:'.$casualMatch->creator_player_id.','.$casualMatch->opponent_player_id,
        ]);

        try {
            $scoring->recordPoint($casualMatch, (int) $validated['entrant_id'], $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $casualMatch->fresh();

        if ($fresh->status === 'completed') {
            $this->applyProgression($fresh);
        }

        $this->broadcastState($fresh);

        return back();
    }

    public function undo(CasualMatch $casualMatch, CasualMatchScoringService $scoring): RedirectResponse
    {
        $this->authorize('score', $casualMatch);

        try {
            $scoring->undoLastPoint($casualMatch);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($casualMatch->fresh());

        return back();
    }

    public function forfeit(Request $request, CasualMatch $casualMatch, CasualMatchScoringService $scoring): RedirectResponse
    {
        $this->authorize('score', $casualMatch);

        $validated = $request->validate([
            'winner_entrant_id' => 'required|integer|in:'.$casualMatch->creator_player_id.','.$casualMatch->opponent_player_id,
        ]);

        try {
            $scoring->forfeit($casualMatch, (int) $validated['winner_entrant_id']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $casualMatch->fresh();
        $this->applyProgression($fresh);
        $this->broadcastState($fresh);

        return back();
    }

    public function cancel(CasualMatch $casualMatch, CasualMatchScoringService $scoring): RedirectResponse
    {
        $this->authorize('score', $casualMatch);

        try {
            $scoring->cancel($casualMatch);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->broadcastState($casualMatch->fresh());

        return redirect()->route('games.index')->with('success', 'Reto cancelado.');
    }

    private function applyProgression(CasualMatch $match): void
    {
        $winner = $match->winner;
        $loser = $match->loser;

        if (! $winner || ! $loser) {
            return;
        }

        if ($match->isRanked()) {
            $this->elo->applyCasualMatchResult($match, $winner, $loser);
        }

        $this->xp->awardForCasualMatch($match, $winner, $loser);
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (CasualMatch::where('code', $code)->exists());

        return $code;
    }

    private function broadcastState(CasualMatch $match): void
    {
        try {
            broadcast(new CasualMatchScoreUpdated($match->id, $this->presenter->present($match)));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
