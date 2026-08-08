<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('match_game_id')->constrained('match_games')->cascadeOnDelete();
            $table->unsignedTinyInteger('point_number');
            $table->foreignId('scoring_entrant_id')->constrained('tournament_registration_divisions')->cascadeOnDelete();
            $table->foreignId('server_entrant_id')->constrained('tournament_registration_divisions')->cascadeOnDelete();
            $table->unsignedTinyInteger('entrant1_points_after');
            $table->unsignedTinyInteger('entrant2_points_after');
            $table->boolean('was_expedite')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['match_game_id', 'point_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_points');
    }
};
