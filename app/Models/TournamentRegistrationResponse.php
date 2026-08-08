<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistrationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_registration_id',
        'tournament_registration_field_id',
        'value',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(TournamentRegistrationField::class, 'tournament_registration_field_id');
    }
}
