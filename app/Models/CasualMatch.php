<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasualMatch extends Model
{
    use HasFactory;

    public const STATUSES = ['waiting', 'ready', 'in_progress', 'completed', 'cancelled'];

    public const TYPES = ['ranked', 'friendly'];

    protected $fillable = [
        'code',
        'match_type',
        'status',
        'best_of',
        'points_to_win',
        'creator_player_id',
        'opponent_player_id',
        'winner_player_id',
        'loser_player_id',
        'score_summary',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'best_of' => 'integer',
            'points_to_win' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'creator_player_id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'opponent_player_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_player_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'loser_player_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(CasualMatchGame::class)->orderBy('game_number');
    }

    public function currentGame(): ?CasualMatchGame
    {
        return $this->games()->whereNull('completed_at')->reorder('game_number', 'desc')->first();
    }

    public function isRanked(): bool
    {
        return $this->match_type === 'ranked';
    }

    public function hasOpponent(): bool
    {
        return $this->opponent_player_id !== null;
    }
}
