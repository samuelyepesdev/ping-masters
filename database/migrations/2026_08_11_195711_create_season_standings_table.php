<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A frozen snapshot of each active player's rank at the moment a season closed —
     * the historical record that survives the next season's rating reset.
     */
    public function up(): void
    {
        Schema::create('season_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('final_rating');
            $table->unsignedInteger('matches_played');
            $table->unsignedInteger('rank');
            $table->timestamps();

            $table->unique(['season_id', 'player_id']);
            $table->index(['season_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_standings');
    }
};
