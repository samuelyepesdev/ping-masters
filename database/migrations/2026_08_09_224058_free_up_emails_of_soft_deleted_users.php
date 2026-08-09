<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-deleted users kept their original email, which still collided with
     * the unique index and blocked new signups under that address. Free up
     * every already soft-deleted row the same way new deletions do now.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->where('email', 'not like', 'deleted-%')
            ->get(['id', 'email'])
            ->each(function ($user) {
                DB::table('users')->where('id', $user->id)->update([
                    'email' => "deleted-{$user->id}-{$user->email}",
                ]);
            });
    }

    /**
     * Irreversible data cleanup — nothing to roll back to.
     */
    public function down(): void
    {
        //
    }
};
