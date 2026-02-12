export type Story = {
    id: number;
    title_zh: string;
    title_pinyin: string;
    title_id: string;
    slug: string;
    description_id: string | null;
    hsk_level: number;
    difficulty_score: number | null;
    word_count: number;
    unique_word_count: number;
    sentence_count: number;
    estimated_minutes: number;
    is_premium: boolean;
    is_published: boolean;
    content_source: string;
    categories: Category[];
};

export type Category = {
    id: number;
    name_id: string;
    name_en: string | null;
    slug: string;
    icon: string | null;
};

export type StorySentence = {
    id: number;
    position: number;
    text_zh: string;
    text_pinyin: string;
    translation_id: string;
    translation_en: string | null;
    words: SentenceWord[];
};

export type SentenceWord = {
    id: number;
    position: number;
    surface_form: string;
    dictionary_entry: DictionaryEntry;
};

export type DictionaryEntry = {
    id: number;
    simplified: string;
    traditional: string | null;
    pinyin: string;
    meaning_id: string | null;
    meaning_en: string | null;
    hsk_level: number | null;
    word_type: string | null;
};

export type ReadingProgress = {
    id: number;
    status: 'not_started' | 'in_progress' | 'completed';
    last_sentence_position: number | null;
    words_saved: number;
    started_at: string | null;
    completed_at: string | null;
};
