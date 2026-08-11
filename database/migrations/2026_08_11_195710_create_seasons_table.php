<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('started_at');
            // A season with ended_at = null is the current, live one — there is always
            // exactly one such row.
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        DB::table('seasons')->insert([
            'name' => 'Temporada 1',
            'started_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
