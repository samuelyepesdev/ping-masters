<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerXpEvent extends Model
{
    use HasFactory;

    public const TYPES = [
        'registration', 'match_played', 'match_won', 'division_champion', 'achievement',
        'casual_match_played', 'casual_match_won',
    ];

    protected $fillable = [
        'player_id',
        'type',
        'amount',
        'tournament_id',
        'tournament_match_id',
        'casual_match_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function casualMatch(): BelongsTo
    {
        return $this->belongsTo(CasualMatch::class);
    }
}
