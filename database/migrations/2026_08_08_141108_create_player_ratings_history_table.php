<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_ratings_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->foreignId('opponent_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedInteger('rating_before');
            $table->unsignedInteger('rating_after');
            $table->timestamps();

            $table->index(['player_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_ratings_history');
    }
};
