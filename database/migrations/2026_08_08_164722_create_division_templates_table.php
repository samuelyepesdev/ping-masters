<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('division_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('category_type', ['singles', 'doubles', 'team'])->default('singles');
            $table->enum('gender_category', ['open', 'male', 'female', 'mixed'])->default('open');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin', 'swiss', 'group_knockout'])->default('single_elimination');
            $table->unsignedTinyInteger('best_of')->default(5);
            $table->unsignedTinyInteger('points_to_win')->default(11);
            $table->unsignedTinyInteger('group_size')->nullable();
            $table->unsignedTinyInteger('advance_per_group')->nullable();
            $table->unsignedTinyInteger('swiss_rounds')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->boolean('seed_by_rating')->default(true);
            $table->timestamps();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_templates');
    }
};
