<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        $admin = User::factory()->create([
            'name' => 'Admin Ping Masters',
            'email' => 'admin@pingmasters.test',
        ]);
        $admin->assignRole('super_admin');
    }
}
