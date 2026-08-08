<?php

namespace App\Services\Xp;

use App\Models\Player;
use App\Models\PlayerXpEvent;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class XpService
{
    private const XP_REGISTRATION = 10;

    private const XP_MATCH_PLAYED = 5;

    private const XP_MATCH_WON = 15;

    private const XP_DIVISION_CHAMPION = 100;

    public function __construct(private readonly LevelService $levels) {}

    public function award(
        Player $player,
        string $type,
        int $amount,
        ?Tournament $tournament = null,
        ?TournamentMatch $match = null,
        ?string $note = null,
    ): void {
        DB::transaction(function () use ($player, $type, $amount, $tournament, $match, $note) {
            PlayerXpEvent::create([
                'player_id' => $player->id,
                'type' => $type,
                'amount' => $amount,
                'tournament_id' => $tournament?->id,
                'tournament_match_id' => $match?->id,
                'note' => $note,
            ]);

            $player->xp_total += $amount;
            $player->level = $this->levels->levelForXp($player->xp_total);
            $player->save();
        });
    }

    public function awardForRegistration(Player $player, Tournament $tournament): void
    {
        $this->award($player, 'registration', self::XP_REGISTRATION, $tournament);
    }

    public function awardForMatch(TournamentMatch $match, Player $winner, Player $loser): void
    {
        $tournament = $match->division->tournament;

        $this->award($winner, 'match_played', self::XP_MATCH_PLAYED, $tournament, $match);
        $this->award($winner, 'match_won', self::XP_MATCH_WON, $tournament, $match);
        $this->award($loser, 'match_played', self::XP_MATCH_PLAYED, $tournament, $match);
    }

    public function awardForDivisionChampion(Player $player, Tournament $tournament, TournamentMatch $anchorMatch): void
    {
        $alreadyAwarded = PlayerXpEvent::where('player_id', $player->id)
            ->where('type', 'division_champion')
            ->where('tournament_match_id', $anchorMatch->id)
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        $this->award($player, 'division_champion', self::XP_DIVISION_CHAMPION, $tournament, $anchorMatch);
    }
}
