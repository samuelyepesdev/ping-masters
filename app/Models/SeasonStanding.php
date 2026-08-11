<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonStanding extends Model
{
    protected $fillable = [
        'season_id',
        'player_id',
        'final_rating',
        'matches_played',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'final_rating' => 'integer',
            'matches_played' => 'integer',
            'rank' => 'integer',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
