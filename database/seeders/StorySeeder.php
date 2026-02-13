<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DictionaryEntry;
use App\Models\SentenceWord;
use App\Models\Story;
use App\Models\StorySentence;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedStory1();
        $this->seedStory2();
        $this->seedStory3();
    }

    /**
     * HSK 1 — Daily life story.
     */
    private function seedStory1(): void
    {
        $story = Story::create([
            'title_zh' => '我的一天',
            'title_pinyin' => 'Wǒ de Yī Tiān',
            'title_id' => 'Hari Saya',
            'description_id' => 'Cerita sederhana tentang rutinitas sehari-hari seorang mahasiswa.',
            'hsk_level' => 1,
            'difficulty_score' => 1.20,
            'word_count' => 24,
            'unique_word_count' => 18,
            'sentence_count' => 4,
            'estimated_minutes' => 2,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $story->categories()->attach(
            Category::where('name_en', 'Daily Life')->first()
        );

        $entries = $this->ensureEntries([
            ['simplified' => '我', 'pinyin' => 'wǒ', 'meaning_id' => 'saya', 'meaning_en' => 'I; me'],
            ['simplified' => '早上', 'pinyin' => 'zǎoshang', 'meaning_id' => 'pagi hari', 'meaning_en' => 'morning'],
            ['simplified' => '六点', 'pinyin' => 'liù diǎn', 'meaning_id' => 'jam enam', 'meaning_en' => 'six o\'clock'],
            ['simplified' => '起床', 'pinyin' => 'qǐchuáng', 'meaning_id' => 'bangun tidur', 'meaning_en' => 'to get up'],
            ['simplified' => '然后', 'pinyin' => 'ránhòu', 'meaning_id' => 'kemudian; lalu', 'meaning_en' => 'then; after that'],
            ['simplified' => '吃', 'pinyin' => 'chī', 'meaning_id' => 'makan', 'meaning_en' => 'to eat'],
            ['simplified' => '早饭', 'pinyin' => 'zǎofàn', 'meaning_id' => 'sarapan', 'meaning_en' => 'breakfast'],
            ['simplified' => '八点', 'pinyin' => 'bā diǎn', 'meaning_id' => 'jam delapan', 'meaning_en' => 'eight o\'clock'],
            ['simplified' => '去', 'pinyin' => 'qù', 'meaning_id' => 'pergi', 'meaning_en' => 'to go'],
            ['simplified' => '学校', 'pinyin' => 'xuéxiào', 'meaning_id' => 'sekolah', 'meaning_en' => 'school'],
            ['simplified' => '上课', 'pinyin' => 'shàngkè', 'meaning_id' => 'mengikuti kelas', 'meaning_en' => 'to attend class'],
            ['simplified' => '下午', 'pinyin' => 'xiàwǔ', 'meaning_id' => 'sore hari', 'meaning_en' => 'afternoon'],
            ['simplified' => '和', 'pinyin' => 'hé', 'meaning_id' => 'dan; dengan', 'meaning_en' => 'and; with'],
            ['simplified' => '朋友', 'pinyin' => 'péngyou', 'meaning_id' => 'teman', 'meaning_en' => 'friend'],
            ['simplified' => '打篮球', 'pinyin' => 'dǎ lánqiú', 'meaning_id' => 'bermain basket', 'meaning_en' => 'to play basketball'],
            ['simplified' => '晚上', 'pinyin' => 'wǎnshang', 'meaning_id' => 'malam hari', 'meaning_en' => 'evening; night'],
            ['simplified' => '做', 'pinyin' => 'zuò', 'meaning_id' => 'melakukan; mengerjakan', 'meaning_en' => 'to do; to make'],
            ['simplified' => '作业', 'pinyin' => 'zuòyè', 'meaning_id' => 'pekerjaan rumah', 'meaning_en' => 'homework'],
        ]);

        $sentences = [
            [
                'text_zh' => '我早上六点起床，然后吃早饭。',
                'text_pinyin' => 'Wǒ zǎoshang liù diǎn qǐchuáng, ránhòu chī zǎofàn.',
                'translation_id' => 'Saya bangun jam enam pagi, lalu sarapan.',
                'translation_en' => 'I get up at six in the morning, then eat breakfast.',
                'words' => [['我', '我'], ['早上', '早上'], ['六点', '六点'], ['起床', '起床'], ['然后', '，然后'], ['吃', '吃'], ['早饭', '早饭。']],
            ],
            [
                'text_zh' => '八点我去学校上课。',
                'text_pinyin' => 'Bā diǎn wǒ qù xuéxiào shàngkè.',
                'translation_id' => 'Jam delapan saya pergi ke sekolah untuk mengikuti kelas.',
                'translation_en' => 'At eight o\'clock I go to school for class.',
                'words' => [['八点', '八点'], ['我', '我'], ['去', '去'], ['学校', '学校'], ['上课', '上课。']],
            ],
            [
                'text_zh' => '下午我和朋友打篮球。',
                'text_pinyin' => 'Xiàwǔ wǒ hé péngyou dǎ lánqiú.',
                'translation_id' => 'Sore hari saya bermain basket dengan teman.',
                'translation_en' => 'In the afternoon I play basketball with friends.',
                'words' => [['下午', '下午'], ['我', '我'], ['和', '和'], ['朋友', '朋友'], ['打篮球', '打篮球。']],
            ],
            [
                'text_zh' => '晚上我做作业，然后起床。',
                'text_pinyin' => 'Wǎnshang wǒ zuò zuòyè, ránhòu qǐchuáng.',
                'translation_id' => 'Malam hari saya mengerjakan PR, lalu tidur.',
                'translation_en' => 'In the evening I do homework, then go to sleep.',
                'words' => [['晚上', '晚上'], ['我', '我'], ['做', '做'], ['作业', '作业'], ['然后', '，然后'], ['起床', '起床。']],
            ],
        ];

        $this->createSentences($story, $sentences, $entries);
    }

    /**
     * HSK 2 — Food & drink story.
     */
    private function seedStory2(): void
    {
        $story = Story::create([
            'title_zh' => '在中国饭馆',
            'title_pinyin' => 'Zài Zhōngguó Fànguǎn',
            'title_id' => 'Di Restoran Cina',
            'description_id' => 'Pengalaman makan di restoran Cina untuk pertama kali.',
            'hsk_level' => 2,
            'difficulty_score' => 2.10,
            'word_count' => 30,
            'unique_word_count' => 22,
            'sentence_count' => 5,
            'estimated_minutes' => 3,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $story->categories()->attach(
            Category::where('name_en', 'Food & Drink')->first()
        );

        $entries = $this->ensureEntries([
            ['simplified' => '今天', 'pinyin' => 'jīntiān', 'meaning_id' => 'hari ini', 'meaning_en' => 'today'],
            ['simplified' => '我们', 'pinyin' => 'wǒmen', 'meaning_id' => 'kami; kita', 'meaning_en' => 'we; us'],
            ['simplified' => '去', 'pinyin' => 'qù', 'meaning_id' => 'pergi', 'meaning_en' => 'to go'],
            ['simplified' => '了', 'pinyin' => 'le', 'meaning_id' => 'partikel lampau', 'meaning_en' => 'past tense particle'],
            ['simplified' => '一家', 'pinyin' => 'yī jiā', 'meaning_id' => 'sebuah (toko/restoran)', 'meaning_en' => 'a (shop/restaurant)'],
            ['simplified' => '中国', 'pinyin' => 'Zhōngguó', 'meaning_id' => 'Tiongkok', 'meaning_en' => 'China'],
            ['simplified' => '饭馆', 'pinyin' => 'fànguǎn', 'meaning_id' => 'restoran', 'meaning_en' => 'restaurant'],
            ['simplified' => '服务员', 'pinyin' => 'fúwùyuán', 'meaning_id' => 'pelayan', 'meaning_en' => 'waiter; waitress'],
            ['simplified' => '给', 'pinyin' => 'gěi', 'meaning_id' => 'memberi', 'meaning_en' => 'to give'],
            ['simplified' => '菜单', 'pinyin' => 'càidān', 'meaning_id' => 'menu', 'meaning_en' => 'menu'],
            ['simplified' => '我', 'pinyin' => 'wǒ', 'meaning_id' => 'saya', 'meaning_en' => 'I; me'],
            ['simplified' => '点', 'pinyin' => 'diǎn', 'meaning_id' => 'memesan', 'meaning_en' => 'to order (food)'],
            ['simplified' => '宫保鸡丁', 'pinyin' => 'gōngbǎo jīdīng', 'meaning_id' => 'ayam kung pao', 'meaning_en' => 'kung pao chicken'],
            ['simplified' => '和', 'pinyin' => 'hé', 'meaning_id' => 'dan; dengan', 'meaning_en' => 'and; with'],
            ['simplified' => '米饭', 'pinyin' => 'mǐfàn', 'meaning_id' => 'nasi', 'meaning_en' => 'rice'],
            ['simplified' => '菜', 'pinyin' => 'cài', 'meaning_id' => 'masakan; sayuran', 'meaning_en' => 'dish; vegetable'],
            ['simplified' => '很', 'pinyin' => 'hěn', 'meaning_id' => 'sangat', 'meaning_en' => 'very'],
            ['simplified' => '好吃', 'pinyin' => 'hǎochī', 'meaning_id' => 'enak; lezat', 'meaning_en' => 'delicious'],
            ['simplified' => '也', 'pinyin' => 'yě', 'meaning_id' => 'juga', 'meaning_en' => 'also; too'],
            ['simplified' => '不', 'pinyin' => 'bù', 'meaning_id' => 'tidak', 'meaning_en' => 'not'],
            ['simplified' => '贵', 'pinyin' => 'guì', 'meaning_id' => 'mahal', 'meaning_en' => 'expensive'],
            ['simplified' => '下次', 'pinyin' => 'xià cì', 'meaning_id' => 'lain kali', 'meaning_en' => 'next time'],
            ['simplified' => '还', 'pinyin' => 'hái', 'meaning_id' => 'masih; lagi', 'meaning_en' => 'still; again'],
            ['simplified' => '想', 'pinyin' => 'xiǎng', 'meaning_id' => 'ingin; mau', 'meaning_en' => 'to want; to think'],
            ['simplified' => '来', 'pinyin' => 'lái', 'meaning_id' => 'datang', 'meaning_en' => 'to come'],
        ]);

        $sentences = [
            [
                'text_zh' => '今天我们去了一家中国饭馆。',
                'text_pinyin' => 'Jīntiān wǒmen qù le yī jiā Zhōngguó fànguǎn.',
                'translation_id' => 'Hari ini kami pergi ke sebuah restoran Cina.',
                'translation_en' => 'Today we went to a Chinese restaurant.',
                'words' => [['今天', '今天'], ['我们', '我们'], ['去', '去'], ['了', '了'], ['一家', '一家'], ['中国', '中国'], ['饭馆', '饭馆。']],
            ],
            [
                'text_zh' => '服务员给我们菜单。',
                'text_pinyin' => 'Fúwùyuán gěi wǒmen càidān.',
                'translation_id' => 'Pelayan memberi kami menu.',
                'translation_en' => 'The waiter gave us the menu.',
                'words' => [['服务员', '服务员'], ['给', '给'], ['我们', '我们'], ['菜单', '菜单。']],
            ],
            [
                'text_zh' => '我点了宫保鸡丁和米饭。',
                'text_pinyin' => 'Wǒ diǎn le gōngbǎo jīdīng hé mǐfàn.',
                'translation_id' => 'Saya memesan ayam kung pao dan nasi.',
                'translation_en' => 'I ordered kung pao chicken and rice.',
                'words' => [['我', '我'], ['点', '点'], ['了', '了'], ['宫保鸡丁', '宫保鸡丁'], ['和', '和'], ['米饭', '米饭。']],
            ],
            [
                'text_zh' => '菜很好吃，也不贵。',
                'text_pinyin' => 'Cài hěn hǎochī, yě bù guì.',
                'translation_id' => 'Masakannya sangat enak, juga tidak mahal.',
                'translation_en' => 'The food was very delicious, and not expensive.',
                'words' => [['菜', '菜'], ['很', '很'], ['好吃', '好吃'], ['也', '，也'], ['不', '不'], ['贵', '贵。']],
            ],
            [
                'text_zh' => '下次我还想来！',
                'text_pinyin' => 'Xià cì wǒ hái xiǎng lái!',
                'translation_id' => 'Lain kali saya masih ingin datang lagi!',
                'translation_en' => 'Next time I still want to come again!',
                'words' => [['下次', '下次'], ['我', '我'], ['还', '还'], ['想', '想'], ['来', '来！']],
            ],
        ];

        $this->createSentences($story, $sentences, $entries);
    }

    /**
     * HSK 3 — Travel story.
     */
    private function seedStory3(): void
    {
        $story = Story::create([
            'title_zh' => '第一次坐飞机',
            'title_pinyin' => 'Dì Yī Cì Zuò Fēijī',
            'title_id' => 'Pertama Kali Naik Pesawat',
            'description_id' => 'Pengalaman pertama kali naik pesawat dari Jakarta ke Beijing.',
            'hsk_level' => 3,
            'difficulty_score' => 2.80,
            'word_count' => 35,
            'unique_word_count' => 28,
            'sentence_count' => 5,
            'estimated_minutes' => 4,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $story->categories()->attach(
            Category::where('name_en', 'Travel')->first()
        );

        $entries = $this->ensureEntries([
            ['simplified' => '去年', 'pinyin' => 'qùnián', 'meaning_id' => 'tahun lalu', 'meaning_en' => 'last year'],
            ['simplified' => '夏天', 'pinyin' => 'xiàtiān', 'meaning_id' => 'musim panas', 'meaning_en' => 'summer'],
            ['simplified' => '我', 'pinyin' => 'wǒ', 'meaning_id' => 'saya', 'meaning_en' => 'I; me'],
            ['simplified' => '第一次', 'pinyin' => 'dì yī cì', 'meaning_id' => 'pertama kali', 'meaning_en' => 'first time'],
            ['simplified' => '坐', 'pinyin' => 'zuò', 'meaning_id' => 'naik (kendaraan)', 'meaning_en' => 'to sit; to ride'],
            ['simplified' => '飞机', 'pinyin' => 'fēijī', 'meaning_id' => 'pesawat terbang', 'meaning_en' => 'airplane'],
            ['simplified' => '去', 'pinyin' => 'qù', 'meaning_id' => 'pergi', 'meaning_en' => 'to go'],
            ['simplified' => '北京', 'pinyin' => 'Běijīng', 'meaning_id' => 'Beijing', 'meaning_en' => 'Beijing'],
            ['simplified' => '在', 'pinyin' => 'zài', 'meaning_id' => 'di; sedang', 'meaning_en' => 'at; in'],
            ['simplified' => '机场', 'pinyin' => 'jīchǎng', 'meaning_id' => 'bandara', 'meaning_en' => 'airport'],
            ['simplified' => '人', 'pinyin' => 'rén', 'meaning_id' => 'orang', 'meaning_en' => 'person'],
            ['simplified' => '非常', 'pinyin' => 'fēicháng', 'meaning_id' => 'sangat; amat', 'meaning_en' => 'very; extremely'],
            ['simplified' => '多', 'pinyin' => 'duō', 'meaning_id' => 'banyak', 'meaning_en' => 'many; much'],
            ['simplified' => '有点儿', 'pinyin' => 'yǒudiǎnr', 'meaning_id' => 'agak; sedikit', 'meaning_en' => 'a little; somewhat'],
            ['simplified' => '紧张', 'pinyin' => 'jǐnzhāng', 'meaning_id' => 'gugup; tegang', 'meaning_en' => 'nervous; tense'],
            ['simplified' => '起飞', 'pinyin' => 'qǐfēi', 'meaning_id' => 'lepas landas', 'meaning_en' => 'to take off'],
            ['simplified' => '的时候', 'pinyin' => 'de shíhou', 'meaning_id' => 'saat; ketika', 'meaning_en' => 'when; at the time of'],
            ['simplified' => '很', 'pinyin' => 'hěn', 'meaning_id' => 'sangat', 'meaning_en' => 'very'],
            ['simplified' => '害怕', 'pinyin' => 'hàipà', 'meaning_id' => 'takut', 'meaning_en' => 'to be afraid'],
            ['simplified' => '但是', 'pinyin' => 'dànshì', 'meaning_id' => 'tetapi; namun', 'meaning_en' => 'but; however'],
            ['simplified' => '从', 'pinyin' => 'cóng', 'meaning_id' => 'dari', 'meaning_en' => 'from'],
            ['simplified' => '窗户', 'pinyin' => 'chuānghu', 'meaning_id' => 'jendela', 'meaning_en' => 'window'],
            ['simplified' => '看到', 'pinyin' => 'kàndào', 'meaning_id' => 'melihat', 'meaning_en' => 'to see'],
            ['simplified' => '云', 'pinyin' => 'yún', 'meaning_id' => 'awan', 'meaning_en' => 'cloud'],
            ['simplified' => '觉得', 'pinyin' => 'juéde', 'meaning_id' => 'merasa', 'meaning_en' => 'to feel; to think'],
            ['simplified' => '漂亮', 'pinyin' => 'piàoliang', 'meaning_id' => 'indah; cantik', 'meaning_en' => 'beautiful; pretty'],
            ['simplified' => '到', 'pinyin' => 'dào', 'meaning_id' => 'sampai; tiba', 'meaning_en' => 'to arrive'],
            ['simplified' => '了', 'pinyin' => 'le', 'meaning_id' => 'partikel lampau', 'meaning_en' => 'past tense particle'],
            ['simplified' => '开心', 'pinyin' => 'kāixīn', 'meaning_id' => 'senang; gembira', 'meaning_en' => 'happy; joyful'],
        ]);

        $sentences = [
            [
                'text_zh' => '去年夏天，我第一次坐飞机去北京。',
                'text_pinyin' => 'Qùnián xiàtiān, wǒ dì yī cì zuò fēijī qù Běijīng.',
                'translation_id' => 'Musim panas tahun lalu, saya pertama kali naik pesawat ke Beijing.',
                'translation_en' => 'Last summer, I took an airplane to Beijing for the first time.',
                'words' => [['去年', '去年'], ['夏天', '夏天'], ['我', '，我'], ['第一次', '第一次'], ['坐', '坐'], ['飞机', '飞机'], ['去', '去'], ['北京', '北京。']],
            ],
            [
                'text_zh' => '在机场，人非常多，我有点儿紧张。',
                'text_pinyin' => 'Zài jīchǎng, rén fēicháng duō, wǒ yǒudiǎnr jǐnzhāng.',
                'translation_id' => 'Di bandara, orangnya sangat banyak, saya agak gugup.',
                'translation_en' => 'At the airport, there were many people, and I was a little nervous.',
                'words' => [['在', '在'], ['机场', '机场'], ['人', '，人'], ['非常', '非常'], ['多', '多'], ['我', '，我'], ['有点儿', '有点儿'], ['紧张', '紧张。']],
            ],
            [
                'text_zh' => '飞机起飞的时候，我很害怕。',
                'text_pinyin' => 'Fēijī qǐfēi de shíhou, wǒ hěn hàipà.',
                'translation_id' => 'Saat pesawat lepas landas, saya sangat takut.',
                'translation_en' => 'When the plane took off, I was very scared.',
                'words' => [['飞机', '飞机'], ['起飞', '起飞'], ['的时候', '的时候'], ['我', '，我'], ['很', '很'], ['害怕', '害怕。']],
            ],
            [
                'text_zh' => '但是从窗户看到云，我觉得很漂亮。',
                'text_pinyin' => 'Dànshì cóng chuānghu kàndào yún, wǒ juéde hěn piàoliang.',
                'translation_id' => 'Tetapi melihat awan dari jendela, saya merasa sangat indah.',
                'translation_en' => 'But seeing the clouds from the window, I felt it was very beautiful.',
                'words' => [['但是', '但是'], ['从', '从'], ['窗户', '窗户'], ['看到', '看到'], ['云', '云'], ['我', '，我'], ['觉得', '觉得'], ['很', '很'], ['漂亮', '漂亮。']],
            ],
            [
                'text_zh' => '到了北京，我非常开心！',
                'text_pinyin' => 'Dào le Běijīng, wǒ fēicháng kāixīn!',
                'translation_id' => 'Sampai di Beijing, saya sangat senang!',
                'translation_en' => 'When I arrived in Beijing, I was extremely happy!',
                'words' => [['到', '到'], ['了', '了'], ['北京', '北京'], ['我', '，我'], ['非常', '非常'], ['开心', '开心！']],
            ],
        ];

        $this->createSentences($story, $sentences, $entries);
    }

    /**
     * Find existing dictionary entry or create one.
     *
     * @param  list<array{simplified: string, pinyin: string, meaning_id: string, meaning_en: string}>  $definitions
     * @return array<string, DictionaryEntry>
     */
    private function ensureEntries(array $definitions): array
    {
        $entries = [];

        foreach ($definitions as $def) {
            $entries[$def['simplified']] = DictionaryEntry::firstOrCreate(
                ['simplified' => $def['simplified'], 'pinyin' => $def['pinyin']],
                [
                    'meaning_id' => $def['meaning_id'],
                    'meaning_en' => $def['meaning_en'],
                ],
            );
        }

        return $entries;
    }

    /**
     * Create sentences and their words for a story.
     *
     * @param  list<array{text_zh: string, text_pinyin: string, translation_id: string, translation_en: string, words: list<array{0: string, 1: string}>}>  $sentencesData
     * @param  array<string, DictionaryEntry>  $entries
     */
    private function createSentences(Story $story, array $sentencesData, array $entries): void
    {
        foreach ($sentencesData as $index => $data) {
            $sentence = StorySentence::create([
                'story_id' => $story->id,
                'position' => $index + 1,
                'text_zh' => $data['text_zh'],
                'text_pinyin' => $data['text_pinyin'],
                'translation_id' => $data['translation_id'],
                'translation_en' => $data['translation_en'],
            ]);

            foreach ($data['words'] as $wordIndex => [$entryKey, $surfaceForm]) {
                SentenceWord::create([
                    'story_sentence_id' => $sentence->id,
                    'dictionary_entry_id' => $entries[$entryKey]->id,
                    'position' => $wordIndex + 1,
                    'surface_form' => $surfaceForm,
                ]);
            }
        }
    }
}
