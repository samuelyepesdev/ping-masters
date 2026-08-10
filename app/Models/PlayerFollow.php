<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerFollow extends Model
{
    protected $fillable = [
        'follower_player_id',
        'followed_player_id',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'follower_player_id');
    }

    public function followed(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'followed_player_id');
    }
}
