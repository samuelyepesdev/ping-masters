<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casual_matches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->enum('match_type', ['ranked', 'friendly']);
            $table->enum('status', ['waiting', 'ready', 'in_progress', 'completed', 'cancelled'])->default('waiting');
            $table->unsignedTinyInteger('best_of')->default(5);
            $table->unsignedTinyInteger('points_to_win')->default(11);
            $table->foreignId('creator_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('opponent_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('winner_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('loser_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('score_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casual_matches');
    }
};
