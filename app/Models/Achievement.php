<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'rule',
        'xp_reward',
    ];

    protected function casts(): array
    {
        return [
            'rule' => 'array',
            'xp_reward' => 'integer',
        ];
    }

    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }
}
