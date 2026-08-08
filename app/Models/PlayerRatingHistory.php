<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRatingHistory extends Model
{
    use HasFactory;

    protected $table = 'player_ratings_history';

    protected $fillable = [
        'player_id',
        'match_id',
        'casual_match_id',
        'opponent_player_id',
        'rating_before',
        'rating_after',
    ];

    protected function casts(): array
    {
        return [
            'rating_before' => 'integer',
            'rating_after' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'opponent_player_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function casualMatch(): BelongsTo
    {
        return $this->belongsTo(CasualMatch::class);
    }
}
