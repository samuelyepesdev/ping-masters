<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_division_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['group_stage', 'swiss', 'winners_bracket', 'losers_bracket', 'main_bracket', 'grand_final'])
                ->default('main_bracket');
            $table->unsignedTinyInteger('round_number');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->index(['tournament_division_id', 'stage', 'round_number'], 'rounds_division_stage_number_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
