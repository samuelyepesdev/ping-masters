<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('casual_matches', function (Blueprint $table) {
            // Only meaningful on ranked matches; the opponent must accept it when joining
            // (see CasualMatchController::join), so its presence on a "ready"+ match implies
            // acceptance — no separate confirmation flag needed.
            $table->unsignedSmallInteger('wager_points')->nullable()->after('points_to_win');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('casual_matches', function (Blueprint $table) {
            $table->dropColumn('wager_points');
        });
    }
};
