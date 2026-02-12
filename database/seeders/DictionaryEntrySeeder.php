<?php

namespace Database\Seeders;

use App\Models\DictionaryEntry;
use Illuminate\Database\Seeder;

class DictionaryEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entries = [
            ['simplified' => '你好', 'pinyin' => 'nǐ hǎo', 'pinyin_numbered' => 'ni3 hao3', 'meaning_id' => 'Halo', 'meaning_en' => 'Hello', 'hsk_level' => 1, 'word_type' => 'interjection'],
            ['simplified' => '谢谢', 'pinyin' => 'xiè xie', 'pinyin_numbered' => 'xie4 xie', 'meaning_id' => 'Terima kasih', 'meaning_en' => 'Thank you', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '我', 'pinyin' => 'wǒ', 'pinyin_numbered' => 'wo3', 'meaning_id' => 'Saya', 'meaning_en' => 'I, me', 'hsk_level' => 1, 'word_type' => 'pronoun'],
            ['simplified' => '你', 'pinyin' => 'nǐ', 'pinyin_numbered' => 'ni3', 'meaning_id' => 'Kamu', 'meaning_en' => 'You', 'hsk_level' => 1, 'word_type' => 'pronoun'],
            ['simplified' => '他', 'pinyin' => 'tā', 'pinyin_numbered' => 'ta1', 'meaning_id' => 'Dia (laki-laki)', 'meaning_en' => 'He, him', 'hsk_level' => 1, 'word_type' => 'pronoun'],
            ['simplified' => '她', 'pinyin' => 'tā', 'pinyin_numbered' => 'ta1', 'meaning_id' => 'Dia (perempuan)', 'meaning_en' => 'She, her', 'hsk_level' => 1, 'word_type' => 'pronoun'],
            ['simplified' => '是', 'pinyin' => 'shì', 'pinyin_numbered' => 'shi4', 'meaning_id' => 'Adalah', 'meaning_en' => 'To be, is', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '不', 'pinyin' => 'bù', 'pinyin_numbered' => 'bu4', 'meaning_id' => 'Tidak', 'meaning_en' => 'Not, no', 'hsk_level' => 1, 'word_type' => 'adverb'],
            ['simplified' => '很', 'pinyin' => 'hěn', 'pinyin_numbered' => 'hen3', 'meaning_id' => 'Sangat', 'meaning_en' => 'Very', 'hsk_level' => 1, 'word_type' => 'adverb'],
            ['simplified' => '好', 'pinyin' => 'hǎo', 'pinyin_numbered' => 'hao3', 'meaning_id' => 'Baik, bagus', 'meaning_en' => 'Good, well', 'hsk_level' => 1, 'word_type' => 'adjective'],
            ['simplified' => '大', 'pinyin' => 'dà', 'pinyin_numbered' => 'da4', 'meaning_id' => 'Besar', 'meaning_en' => 'Big, large', 'hsk_level' => 1, 'word_type' => 'adjective'],
            ['simplified' => '小', 'pinyin' => 'xiǎo', 'pinyin_numbered' => 'xiao3', 'meaning_id' => 'Kecil', 'meaning_en' => 'Small, little', 'hsk_level' => 1, 'word_type' => 'adjective'],
            ['simplified' => '人', 'pinyin' => 'rén', 'pinyin_numbered' => 'ren2', 'meaning_id' => 'Orang', 'meaning_en' => 'Person, people', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '中国', 'pinyin' => 'zhōng guó', 'pinyin_numbered' => 'zhong1 guo2', 'meaning_id' => 'Tiongkok', 'meaning_en' => 'China', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '学生', 'pinyin' => 'xué shēng', 'pinyin_numbered' => 'xue2 sheng1', 'meaning_id' => 'Murid, pelajar', 'meaning_en' => 'Student', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '老师', 'pinyin' => 'lǎo shī', 'pinyin_numbered' => 'lao3 shi1', 'meaning_id' => 'Guru', 'meaning_en' => 'Teacher', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '朋友', 'pinyin' => 'péng you', 'pinyin_numbered' => 'peng2 you', 'meaning_id' => 'Teman', 'meaning_en' => 'Friend', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '吃', 'pinyin' => 'chī', 'pinyin_numbered' => 'chi1', 'meaning_id' => 'Makan', 'meaning_en' => 'To eat', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '喝', 'pinyin' => 'hē', 'pinyin_numbered' => 'he1', 'meaning_id' => 'Minum', 'meaning_en' => 'To drink', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '看', 'pinyin' => 'kàn', 'pinyin_numbered' => 'kan4', 'meaning_id' => 'Melihat, membaca', 'meaning_en' => 'To look, to read', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '说', 'pinyin' => 'shuō', 'pinyin_numbered' => 'shuo1', 'meaning_id' => 'Berbicara, berkata', 'meaning_en' => 'To speak, to say', 'hsk_level' => 1, 'word_type' => 'verb'],
            ['simplified' => '猫', 'pinyin' => 'māo', 'pinyin_numbered' => 'mao1', 'meaning_id' => 'Kucing', 'meaning_en' => 'Cat', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '狗', 'pinyin' => 'gǒu', 'pinyin_numbered' => 'gou3', 'meaning_id' => 'Anjing', 'meaning_en' => 'Dog', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '水', 'pinyin' => 'shuǐ', 'pinyin_numbered' => 'shui3', 'meaning_id' => 'Air', 'meaning_en' => 'Water', 'hsk_level' => 1, 'word_type' => 'noun'],
            ['simplified' => '可爱', 'pinyin' => 'kě ài', 'pinyin_numbered' => 'ke3 ai4', 'meaning_id' => 'Lucu, menggemaskan', 'meaning_en' => 'Cute, adorable', 'hsk_level' => 2, 'word_type' => 'adjective'],
        ];

        foreach ($entries as $entry) {
            DictionaryEntry::create($entry);
        }
    }
}
