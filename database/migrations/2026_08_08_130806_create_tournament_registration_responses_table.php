<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registration_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_registration_id')
                ->constrained('tournament_registrations', 'id', 'trr_registration_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('tournament_registration_field_id')
                ->constrained('tournament_registration_fields', 'id', 'trr_field_id_foreign')
                ->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['tournament_registration_id', 'tournament_registration_field_id'], 'trr_registration_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_responses');
    }
};
