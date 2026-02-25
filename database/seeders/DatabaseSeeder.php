<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            BlogCategorySeeder::class,
            DictionaryEntrySeeder::class,
            StorySeeder::class,
            SeriesSeeder::class,
        ]);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@nihao.test',
        ]);
        $admin->assignRole('admin');
        $admin->preference()->create([]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->assignRole('user');
        $user->preference()->create([]);
    }
}
