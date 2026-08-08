<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasualMatchPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'casual_match_id',
        'casual_match_game_id',
        'point_number',
        'scoring_player_id',
        'server_player_id',
        'creator_points_after',
        'opponent_points_after',
        'was_expedite',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'point_number' => 'integer',
            'creator_points_after' => 'integer',
            'opponent_points_after' => 'integer',
            'was_expedite' => 'boolean',
        ];
    }

    public function casualMatch(): BelongsTo
    {
        return $this->belongsTo(CasualMatch::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(CasualMatchGame::class, 'casual_match_game_id');
    }

    public function scoringPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'scoring_player_id');
    }

    public function serverPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'server_player_id');
    }
}
