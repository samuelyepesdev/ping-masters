<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('handedness', ['left', 'right'])->nullable();
            $table->string('playing_style')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('rating_current')->default(1000);
            $table->unsignedInteger('rating_deviation')->default(350);
            $table->unsignedInteger('matches_played_rated')->default(0);
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('matches_won')->default(0);
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->boolean('is_elite')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
