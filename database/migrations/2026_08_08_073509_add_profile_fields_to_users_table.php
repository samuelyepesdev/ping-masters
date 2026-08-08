<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar_path');
            $table->string('country')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('country');
            $table->enum('gender', ['male', 'female', 'other', 'undisclosed'])->default('undisclosed')->after('date_of_birth');
            $table->foreignId('club_id')->nullable()->after('gender')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_id');
            $table->dropColumn(['avatar_path', 'phone', 'country', 'date_of_birth', 'gender']);
        });
    }
};
