<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name_id' => 'Kehidupan Sehari-hari', 'name_en' => 'Daily Life', 'icon' => 'sun'],
            ['name_id' => 'Makanan & Minuman', 'name_en' => 'Food & Drink', 'icon' => 'utensils'],
            ['name_id' => 'Perjalanan', 'name_en' => 'Travel', 'icon' => 'plane'],
            ['name_id' => 'Budaya', 'name_en' => 'Culture', 'icon' => 'landmark'],
            ['name_id' => 'Bisnis', 'name_en' => 'Business', 'icon' => 'briefcase'],
            ['name_id' => 'Teknologi', 'name_en' => 'Technology', 'icon' => 'laptop'],
            ['name_id' => 'Cerita Rakyat', 'name_en' => 'Folk Tales', 'icon' => 'book-open'],
            ['name_id' => 'Keluarga', 'name_en' => 'Family', 'icon' => 'users'],
            ['name_id' => 'Sekolah', 'name_en' => 'School', 'icon' => 'graduation-cap'],
            ['name_id' => 'Alam', 'name_en' => 'Nature', 'icon' => 'tree-pine'],
        ];

        foreach ($categories as $index => $category) {
            Category::create([...$category, 'sort_order' => $index]);
        }
    }
}
