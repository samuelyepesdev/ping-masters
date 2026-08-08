<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registration_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_registration_id')
                ->constrained('tournament_registrations', 'id', 'trd_registration_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('tournament_division_id')
                ->constrained('tournament_divisions', 'id', 'trd_division_id_foreign')
                ->cascadeOnDelete();
            $table->string('partner_name')->nullable();
            $table->string('partner_club')->nullable();
            $table->unsignedInteger('seed_rating_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['tournament_registration_id', 'tournament_division_id'], 'trd_registration_division_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_divisions');
    }
};
