<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casual_match_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casual_match_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('game_number');
            $table->foreignId('first_server_player_id')->nullable()
                ->constrained('players', 'id', 'casual_match_games_first_server_foreign')
                ->nullOnDelete();
            $table->unsignedTinyInteger('creator_points')->default(0);
            $table->unsignedTinyInteger('opponent_points')->default(0);
            $table->foreignId('winner_player_id')->nullable()
                ->constrained('players', 'id', 'casual_match_games_winner_foreign')
                ->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['casual_match_id', 'game_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casual_match_games');
    }
};
