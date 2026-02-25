<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Tips Belajar', 'description' => 'Tips dan trik belajar bahasa Mandarin'],
            ['name' => 'Budaya Tiongkok', 'description' => 'Mengenal budaya dan tradisi Tiongkok'],
            ['name' => 'Tata Bahasa', 'description' => 'Penjelasan tata bahasa Mandarin'],
            ['name' => 'Kosakata', 'description' => 'Kosakata penting untuk pemula hingga mahir'],
            ['name' => 'Cerita Pengguna', 'description' => 'Pengalaman pengguna belajar Mandarin'],
        ];

        foreach ($categories as $index => $category) {
            BlogCategory::create([...$category, 'sort_order' => $index]);
        }
    }
}
