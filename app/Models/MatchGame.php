<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'game_number',
        'first_server_entrant_id',
        'entrant1_points',
        'entrant2_points',
        'winner_entrant_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'game_number' => 'integer',
            'entrant1_points' => 'integer',
            'entrant2_points' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function firstServerEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'first_server_entrant_id');
    }

    public function winnerEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'winner_entrant_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(MatchPoint::class)->orderBy('point_number');
    }

    public function isComplete(): bool
    {
        return $this->winner_entrant_id !== null;
    }
}
