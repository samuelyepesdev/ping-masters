<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    public const STAGES = ['group_stage', 'swiss', 'winners_bracket', 'losers_bracket', 'main_bracket', 'grand_final'];

    protected $fillable = [
        'tournament_division_id',
        'stage',
        'round_number',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TournamentDivision::class, 'tournament_division_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'round_id');
    }
}
