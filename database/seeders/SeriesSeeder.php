<?php

namespace Database\Seeders;

use App\Models\Series;
use App\Models\Story;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $series = Series::create([
            'title_zh' => '生活在中国',
            'title_pinyin' => 'Shēnghuó Zài Zhōngguó',
            'title_id' => 'Kehidupan di Tiongkok',
            'description_id' => 'Kumpulan cerita tentang kehidupan sehari-hari di Tiongkok, mulai dari rutinitas harian, makan di restoran, hingga pengalaman pertama naik pesawat.',
            'hsk_level' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $storyMappings = [
            '我的一天' => 1,
            '在中国饭馆' => 2,
            '第一次坐飞机' => 3,
        ];

        foreach ($storyMappings as $titleZh => $order) {
            Story::where('title_zh', $titleZh)->update([
                'series_id' => $series->id,
                'series_order' => $order,
            ]);
        }
    }
}
