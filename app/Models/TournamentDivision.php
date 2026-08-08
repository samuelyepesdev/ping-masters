<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
        'category_type',
        'gender_category',
        'min_age',
        'max_age',
        'format',
        'best_of',
        'points_to_win',
        'group_size',
        'advance_per_group',
        'swiss_rounds',
        'max_participants',
        'seed_by_rating',
        'status',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'best_of' => 'integer',
            'points_to_win' => 'integer',
            'group_size' => 'integer',
            'advance_per_group' => 'integer',
            'swiss_rounds' => 'integer',
            'max_participants' => 'integer',
            'seed_by_rating' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrationDivisions(): HasMany
    {
        return $this->hasMany(TournamentRegistrationDivision::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class)->orderBy('display_order');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_number');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function approvedEntrants(): HasMany
    {
        return $this->registrationDivisions()->whereHas('registration', function ($query) {
            $query->where('status', 'approved');
        });
    }
}
