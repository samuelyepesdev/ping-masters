<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
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
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
