<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    public const STATUSES = ['pending', 'ready', 'in_progress', 'completed', 'walkover', 'cancelled'];

    protected $fillable = [
        'tournament_division_id',
        'round_id',
        'tournament_group_id',
        'match_number',
        'entrant1_id',
        'entrant2_id',
        'entrant1_is_bye',
        'entrant2_is_bye',
        'entrant1_source_match_id',
        'entrant1_source_type',
        'entrant2_source_match_id',
        'entrant2_source_type',
        'entrant1_source_group_id',
        'entrant1_source_group_rank',
        'entrant2_source_group_id',
        'entrant2_source_group_rank',
        'winner_entrant_id',
        'loser_entrant_id',
        'status',
        'table_number',
        'scheduled_at',
        'completed_at',
        'score_summary',
    ];

    protected function casts(): array
    {
        return [
            'entrant1_is_bye' => 'boolean',
            'entrant2_is_bye' => 'boolean',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TournamentDivision::class, 'tournament_division_id');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'tournament_group_id');
    }

    public function entrant1(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'entrant1_id');
    }

    public function entrant2(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'entrant2_id');
    }

    public function winnerEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'winner_entrant_id');
    }

    public function loserEntrant(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationDivision::class, 'loser_entrant_id');
    }

    public function entrant1SourceMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'entrant1_source_match_id');
    }

    public function entrant2SourceMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'entrant2_source_match_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'match_id')->orderBy('game_number');
    }

    public function currentGame(): ?MatchGame
    {
        return $this->games()->whereNull('completed_at')->reorder('game_number', 'desc')->first();
    }

    public function isBye(): bool
    {
        return $this->entrant1_is_bye || $this->entrant2_is_bye;
    }

    public function bothEntrantsKnown(): bool
    {
        return $this->entrant1_id !== null && $this->entrant2_id !== null;
    }
}
