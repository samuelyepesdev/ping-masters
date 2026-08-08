<?php

namespace App\Services\Achievements;

use App\Models\Achievement;
use App\Models\Player;
use App\Models\PlayerAchievement;
use App\Models\PlayerXpEvent;
use App\Models\TournamentMatch;
use App\Services\Xp\XpService;

class AchievementEngine
{
    public function __construct(private readonly XpService $xp) {}

    public function evaluateForPlayer(Player $player): void
    {
        $unlockedIds = $player->achievements()->pluck('achievements.id')->all();

        foreach (Achievement::all() as $achievement) {
            if (in_array($achievement->id, $unlockedIds, true)) {
                continue;
            }

            if ($this->meetsRule($player, $achievement->rule)) {
                $this->unlock($player, $achievement);
            }
        }
    }

    private function meetsRule(Player $player, array $rule): bool
    {
        return match ($rule['type'] ?? null) {
            'matches_won_total' => $player->matches_won >= $rule['threshold'],
            'matches_played_total' => $player->matches_played >= $rule['threshold'],
            'win_streak' => $this->currentWinStreak($player) >= $rule['threshold'],
            'tournament_champion' => $this->championshipCount($player) >= $rule['threshold'],
            default => false,
        };
    }

    private function unlock(Player $player, Achievement $achievement): void
    {
        PlayerAchievement::create([
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
        ]);

        if ($achievement->xp_reward > 0) {
            $this->xp->award($player, 'achievement', $achievement->xp_reward, note: $achievement->name);
        }
    }

    private function currentWinStreak(Player $player): int
    {
        $matches = TournamentMatch::where('status', 'completed')
            ->where(function ($query) use ($player) {
                $query->whereHas('winnerEntrant.registration', fn ($q) => $q->where('player_id', $player->id))
                    ->orWhereHas('loserEntrant.registration', fn ($q) => $q->where('player_id', $player->id));
            })
            ->orderByDesc('completed_at')
            ->get();

        $streak = 0;

        foreach ($matches as $match) {
            $wonThisMatch = $match->winnerEntrant?->registration?->player_id === $player->id;

            if (! $wonThisMatch) {
                break;
            }

            $streak++;
        }

        return $streak;
    }

    private function championshipCount(Player $player): int
    {
        return PlayerXpEvent::where('player_id', $player->id)->where('type', 'division_champion')->count();
    }
}
