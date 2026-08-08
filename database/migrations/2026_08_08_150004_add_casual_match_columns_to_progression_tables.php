<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_ratings_history', function (Blueprint $table) {
            $table->foreignId('casual_match_id')->nullable()->after('match_id')->constrained('casual_matches')->nullOnDelete();
        });

        Schema::table('player_xp_events', function (Blueprint $table) {
            $table->foreignId('casual_match_id')->nullable()->after('tournament_match_id')->constrained('casual_matches')->nullOnDelete();
        });

        Schema::table('player_xp_events', function (Blueprint $table) {
            $table->enum('type', [
                'registration', 'match_played', 'match_won', 'division_champion', 'achievement',
                'casual_match_played', 'casual_match_won',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('player_xp_events', function (Blueprint $table) {
            $table->enum('type', ['registration', 'match_played', 'match_won', 'division_champion', 'achievement'])->change();
        });

        Schema::table('player_xp_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casual_match_id');
        });

        Schema::table('player_ratings_history', function (Blueprint $table) {
            $table->dropConstrainedForeignId('casual_match_id');
        });
    }
};
