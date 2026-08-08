<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'code' => 'first_win',
                'name' => 'Primera Victoria',
                'description' => 'Gana tu primer partido oficial.',
                'icon' => '🏓',
                'rule' => ['type' => 'matches_won_total', 'threshold' => 1],
                'xp_reward' => 20,
            ],
            [
                'code' => 'ten_wins',
                'name' => 'Diez Victorias',
                'description' => 'Acumula 10 victorias.',
                'icon' => '🥉',
                'rule' => ['type' => 'matches_won_total', 'threshold' => 10],
                'xp_reward' => 40,
            ],
            [
                'code' => 'veteran',
                'name' => 'Veterano',
                'description' => 'Juega 20 partidos.',
                'icon' => '🎽',
                'rule' => ['type' => 'matches_played_total', 'threshold' => 20],
                'xp_reward' => 30,
            ],
            [
                'code' => 'win_streak_5',
                'name' => 'Racha de 5',
                'description' => 'Gana 5 partidos seguidos.',
                'icon' => '🔥',
                'rule' => ['type' => 'win_streak', 'threshold' => 5],
                'xp_reward' => 50,
            ],
            [
                'code' => 'champion',
                'name' => 'Campeón',
                'description' => 'Gana una categoría de un torneo.',
                'icon' => '🏆',
                'rule' => ['type' => 'tournament_champion', 'threshold' => 1],
                'xp_reward' => 100,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['code' => $achievement['code']], $achievement);
        }
    }
}
