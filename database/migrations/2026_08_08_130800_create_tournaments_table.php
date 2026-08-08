<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['draft', 'registration_open', 'registration_closed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_opens_at')->nullable();
            $table->date('registration_closes_at')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
