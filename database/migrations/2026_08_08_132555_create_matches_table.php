<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_division_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->nullable()->constrained('rounds')->cascadeOnDelete();
            $table->foreignId('tournament_group_id')->nullable()->constrained('tournament_groups')->cascadeOnDelete();
            $table->unsignedInteger('match_number')->default(0);

            // An "entrant" is a tournament_registration_divisions row: one player (singles/team)
            // or one pair (doubles, via partner_name on that same row).
            $table->foreignId('entrant1_id')->nullable()->constrained('tournament_registration_divisions', 'id', 'matches_entrant1_foreign')->nullOnDelete();
            $table->foreignId('entrant2_id')->nullable()->constrained('tournament_registration_divisions', 'id', 'matches_entrant2_foreign')->nullOnDelete();
            $table->boolean('entrant1_is_bye')->default(false);
            $table->boolean('entrant2_is_bye')->default(false);

            // Elimination-style advancement: this entrant slot is filled by the winner/loser of another match.
            $table->foreignId('entrant1_source_match_id')->nullable()->constrained('matches', 'id', 'matches_entrant1_source_foreign')->nullOnDelete();
            $table->enum('entrant1_source_type', ['winner', 'loser'])->nullable();
            $table->foreignId('entrant2_source_match_id')->nullable()->constrained('matches', 'id', 'matches_entrant2_source_foreign')->nullOnDelete();
            $table->enum('entrant2_source_type', ['winner', 'loser'])->nullable();

            // Group-stage advancement: this entrant slot is filled by whoever finishes Nth in a group.
            $table->foreignId('entrant1_source_group_id')->nullable()->constrained('tournament_groups', 'id', 'matches_entrant1_group_foreign')->nullOnDelete();
            $table->unsignedTinyInteger('entrant1_source_group_rank')->nullable();
            $table->foreignId('entrant2_source_group_id')->nullable()->constrained('tournament_groups', 'id', 'matches_entrant2_group_foreign')->nullOnDelete();
            $table->unsignedTinyInteger('entrant2_source_group_rank')->nullable();

            $table->foreignId('winner_entrant_id')->nullable()->constrained('tournament_registration_divisions', 'id', 'matches_winner_entrant_foreign')->nullOnDelete();
            $table->foreignId('loser_entrant_id')->nullable()->constrained('tournament_registration_divisions', 'id', 'matches_loser_entrant_foreign')->nullOnDelete();

            $table->enum('status', ['pending', 'ready', 'in_progress', 'completed', 'walkover', 'cancelled'])->default('pending');
            $table->unsignedSmallInteger('table_number')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('score_summary')->nullable();
            $table->timestamps();

            $table->index(['tournament_division_id', 'round_id']);
            $table->index(['tournament_division_id', 'tournament_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
