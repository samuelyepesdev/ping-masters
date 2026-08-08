<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentRegistrationField extends Model
{
    use HasFactory;

    public const TYPES = ['text', 'textarea', 'number', 'email', 'phone', 'date', 'select', 'radio', 'checkbox', 'checkbox_group'];

    public const CHOICE_TYPES = ['select', 'radio', 'checkbox_group'];

    protected $fillable = [
        'tournament_id',
        'label',
        'field_type',
        'options',
        'placeholder',
        'help_text',
        'is_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TournamentRegistrationResponse::class);
    }
}
