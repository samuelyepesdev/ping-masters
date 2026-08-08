<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'match_game_id',
        'point_number',
        'scoring_entrant_id',
        'server_entrant_id',
        'entrant1_points_after',
        'entrant2_points_after',
        'was_expedite',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'point_number' => 'integer',
            'entrant1_points_after' => 'integer',
            'entrant2_points_after' => 'integer',
            'was_expedite' => 'boolean',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_game_id');
    }

    public function scoringEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'scoring_entrant_id');
    }

    public function serverEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'server_entrant_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
