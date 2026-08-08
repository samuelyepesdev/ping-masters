<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasualMatchGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'casual_match_id',
        'game_number',
        'first_server_player_id',
        'creator_points',
        'opponent_points',
        'winner_player_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'game_number' => 'integer',
            'creator_points' => 'integer',
            'opponent_points' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function casualMatch(): BelongsTo
    {
        return $this->belongsTo(CasualMatch::class);
    }

    public function firstServerPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'first_server_player_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_player_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(CasualMatchPoint::class)->orderBy('point_number');
    }

    public function isComplete(): bool
    {
        return $this->winner_player_id !== null;
    }
}
