<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'venue',
        'city',
        'cover_image_path',
        'club_id',
        'created_by',
        'status',
        'is_active',
        'start_date',
        'end_date',
        'registration_opens_at',
        'registration_closes_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_opens_at' => 'date',
            'registration_closes_at' => 'date',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(TournamentDivision::class)->orderBy('display_order');
    }

    public function registrationFields(): HasMany
    {
        return $this->hasMany(TournamentRegistrationField::class)->orderBy('display_order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status !== 'registration_open') {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->registration_opens_at && $today->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->registration_closes_at && $today->gt($this->registration_closes_at)) {
            return false;
        }

        return true;
    }
}
