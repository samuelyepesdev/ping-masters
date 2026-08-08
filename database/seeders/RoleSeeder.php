<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the base roles used across the platform.
     */
    public function run(): void
    {
        foreach (['super_admin', 'organizer', 'referee', 'player'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
