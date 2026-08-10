<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'club_id',
        'handedness',
        'playing_style',
        'height_cm',
        'bio',
        'rating_current',
        'rating_deviation',
        'matches_played_rated',
        'matches_played',
        'matches_won',
        'xp_total',
        'level',
        'is_elite',
    ];

    protected function casts(): array
    {
        return [
            'is_elite' => 'boolean',
            'rating_current' => 'integer',
            'rating_deviation' => 'integer',
            'matches_played_rated' => 'integer',
            'matches_played' => 'integer',
            'matches_won' => 'integer',
            'xp_total' => 'integer',
            'level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        // Kept visible even after the account is soft-deleted, so this player's
        // name still shows up in past tournament/match history.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function tournamentRegistrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function ratingHistory(): HasMany
    {
        return $this->hasMany(PlayerRatingHistory::class)->orderBy('created_at');
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(PlayerXpEvent::class);
    }

    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }

    public function createdCasualMatches(): HasMany
    {
        return $this->hasMany(CasualMatch::class, 'creator_player_id');
    }

    public function joinedCasualMatches(): HasMany
    {
        return $this->hasMany(CasualMatch::class, 'opponent_player_id');
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements')->withPivot('unlocked_at');
    }

    /**
     * Players this player follows.
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_follows', 'follower_player_id', 'followed_player_id')->withTimestamps();
    }

    /**
     * Players following this player.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_follows', 'followed_player_id', 'follower_player_id')->withTimestamps();
    }

    public function isFollowing(Player $player): bool
    {
        return $this->following()->where('players.id', $player->id)->exists();
    }

    public function winRate(): float
    {
        if ($this->matches_played === 0) {
            return 0.0;
        }

        return round(($this->matches_won / $this->matches_played) * 100, 1);
    }
}
