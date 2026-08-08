<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistrationDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_registration_id',
        'tournament_division_id',
        'partner_name',
        'partner_club',
        'seed_rating_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'seed_rating_snapshot' => 'integer',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TournamentDivision::class, 'tournament_division_id');
    }
}
