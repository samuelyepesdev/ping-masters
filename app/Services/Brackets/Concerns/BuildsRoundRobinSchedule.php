<?php

namespace App\Services\Brackets\Concerns;

trait BuildsRoundRobinSchedule
{
    /**
     * Circle method round-robin schedule. Returns one array of [entrant, entrant] pairs per round;
     * a null entrant represents the "bye" slot used to make an odd entrant count even.
     *
     * @param  array<int, mixed>  $entrants
     * @return array<int, array<int, array{0: mixed, 1: mixed}>>
     */
    private function buildRoundRobinSchedule(array $entrants): array
    {
        if (count($entrants) % 2 !== 0) {
            $entrants[] = null;
        }

        $n = count($entrants);
        $rounds = $n - 1;
        $fixed = $entrants[0];
        $rotating = array_slice($entrants, 1);

        $schedule = [];

        for ($r = 0; $r < $rounds; $r++) {
            $current = array_merge([$fixed], $rotating);
            $pairs = [];

            for ($i = 0; $i < $n / 2; $i++) {
                $pairs[] = [$current[$i], $current[$n - 1 - $i]];
            }

            $schedule[] = $pairs;

            array_unshift($rotating, array_pop($rotating));
        }

        return $schedule;
    }
}
