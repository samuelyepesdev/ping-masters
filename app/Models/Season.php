<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = [
        'name',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function standings(): HasMany
    {
        return $this->hasMany(SeasonStanding::class)->orderBy('rank');
    }

    public function isCurrent(): bool
    {
        return $this->ended_at === null;
    }

    public static function current(): self
    {
        return static::whereNull('ended_at')->latest('started_at')->firstOrFail();
    }
}
