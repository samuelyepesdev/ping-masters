<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ELO K-factor tiers
    |--------------------------------------------------------------------------
    |
    | K controls how much a single result moves a player's rating. New players
    | move fast so their rating finds its true level quickly; established elite
    | players move slowly so a single upset doesn't swing their rating wildly.
    */

    'k_factor_new_player' => 40,
    'k_factor_new_player_max_matches' => 30,

    'k_factor_intermediate' => 32,
    'k_factor_intermediate_max_matches' => 60,

    'k_factor_standard' => 24,

    'k_factor_elite' => 16,
    'elite_rating_threshold' => 2200,
    'elite_min_matches' => 100,

    'starting_rating' => 1000,
    'starting_rating_deviation' => 350,
];
