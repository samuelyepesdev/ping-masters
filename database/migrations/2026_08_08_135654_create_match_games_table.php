<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('game_number');
            $table->foreignId('first_server_entrant_id')->nullable()
                ->constrained('tournament_registration_divisions', 'id', 'match_games_first_server_foreign')
                ->nullOnDelete();
            $table->unsignedTinyInteger('entrant1_points')->default(0);
            $table->unsignedTinyInteger('entrant2_points')->default(0);
            $table->foreignId('winner_entrant_id')->nullable()
                ->constrained('tournament_registration_divisions', 'id', 'match_games_winner_foreign')
                ->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'game_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
