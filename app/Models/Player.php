<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
        'handedness',
        'playing_style',
        'height_cm',
        'bio',
        'rating_current',
        'rating_deviation',
        'matches_played_rated',
        'matches_played',
        'matches_won',
        'xp_total',
        'level',
        'is_elite',
    ];

    protected function casts(): array
    {
        return [
            'is_elite' => 'boolean',
            'rating_current' => 'integer',
            'rating_deviation' => 'integer',
            'matches_played_rated' => 'integer',
            'matches_played' => 'integer',
            'matches_won' => 'integer',
            'xp_total' => 'integer',
            'level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function winRate(): float
    {
        if ($this->matches_played === 0) {
            return 0.0;
        }

        return round(($this->matches_won / $this->matches_played) * 100, 1);
    }
}
