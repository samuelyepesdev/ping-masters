<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GeneratedDocument extends Model
{
    use HasFactory;

    public const TYPES = ['bracket', 'scorecard', 'schedule', 'certificate'];

    protected $fillable = [
        'type',
        'documentable_type',
        'documentable_id',
        'generated_by',
        'title',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
