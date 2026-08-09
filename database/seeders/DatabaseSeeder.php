<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(LevelSeeder::class);
        $this->call(AchievementSeeder::class);

        // ADMIN_EMAIL/ADMIN_PASSWORD let production set real credentials instead of
        // the well-known local-dev default (see config/admin.php). Re-running this
        // seeder is safe: it updates the existing admin rather than duplicating it.
        $admin = User::updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => 'Admin Ping Masters',
                'password' => Hash::make(config('admin.password')),
                'email_verified_at' => now(),
            ],
        );
        $admin->assignRole('super_admin');
    }
}
