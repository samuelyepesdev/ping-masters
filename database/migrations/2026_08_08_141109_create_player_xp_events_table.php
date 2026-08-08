<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['registration', 'match_played', 'match_won', 'division_champion', 'achievement']);
            $table->unsignedInteger('amount');
            $table->foreignId('tournament_id')->nullable()->constrained('tournaments')->nullOnDelete();
            $table->foreignId('tournament_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_xp_events');
    }
};
