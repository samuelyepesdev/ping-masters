<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casual_match_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casual_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('casual_match_game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('point_number');
            $table->foreignId('scoring_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('server_player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedTinyInteger('creator_points_after');
            $table->unsignedTinyInteger('opponent_points_after');
            $table->boolean('was_expedite')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['casual_match_game_id', 'point_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casual_match_points');
    }
};
