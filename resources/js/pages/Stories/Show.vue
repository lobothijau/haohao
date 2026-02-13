<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Clock, BookOpen, ArrowLeft, CheckCircle } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Popover, PopoverTrigger } from '@/components/ui/popover';
import ReaderControls from '@/components/stories/ReaderControls.vue';
import WordTooltip from '@/components/stories/WordTooltip.vue';
import { progress as progressRoute } from '@/routes/stories';
import type {
    Story,
    StorySentence,
    SentenceWord,
    ReadingProgress,
    UserPreferences,
} from '@/types';

const props = defineProps<{
    story: Story;
    sentences: StorySentence[];
    progress: ReadingProgress | null;
    savedVocabularyIds: number[];
    preferences: UserPreferences | null;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const showPinyin = ref(JSON.parse(localStorage.getItem('pref:show_pinyin') ?? 'true'));
const showTranslation = ref(JSON.parse(localStorage.getItem('pref:show_translation') ?? 'false'));
const selectedWordId = ref<number | null>(null);
const savedIds = ref<Set<number>>(new Set(props.savedVocabularyIds));
const isCompleted = ref(props.progress?.status === 'completed');

const fontSizeSteps = [16, 18, 20, 24, 28, 32];
const fontSizeIndex = ref(Number(localStorage.getItem('pref:font_size_index') ?? 2));
const fontSize = computed(() => `${fontSizeSteps[fontSizeIndex.value]}px`);

function increaseFontSize(): void {
    if (fontSizeIndex.value < fontSizeSteps.length - 1) {
        fontSizeIndex.value++;
    }
}

function decreaseFontSize(): void {
    if (fontSizeIndex.value > 0) {
        fontSizeIndex.value--;
    }
}

function savePreferences(): void {
    localStorage.setItem('pref:show_pinyin', JSON.stringify(showPinyin.value));
    localStorage.setItem('pref:show_translation', JSON.stringify(showTranslation.value));
    localStorage.setItem('pref:font_size_index', String(fontSizeIndex.value));
}

watch([showPinyin, showTranslation, fontSizeIndex], savePreferences);

function splitPunctuation(text: string): { before: string; word: string; after: string } {
    const match = text.match(/^([\p{P}\p{S}]*)(.*?)([\p{P}\p{S}]*)$/u);
    if (!match) {
        return { before: '', word: text, after: '' };
    }
    return { before: match[1], word: match[2], after: match[3] };
}

function isWordSaved(word: SentenceWord): boolean {
    return savedIds.value.has(word.dictionary_entry.id);
}

function onWordSaved(dictionaryEntryId: number): void {
    savedIds.value.add(dictionaryEntryId);
}

function updateProgress(position: number, status: string): void {
    if (!isAuthenticated.value) {
        return;
    }
    router.post(
        progressRoute(props.story).url,
        {
            last_sentence_position: position,
            status,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function showPinyinBasedOnLevel(word: SentenceWord): boolean {
    const wordLevel = word.dictionary_entry.hsk_level;
    if (!wordLevel) {
        return true;
    }
    return wordLevel >= props.story.hsk_level;
}

function markComplete(): void {
    isCompleted.value = true;
    updateProgress(props.sentences.length, 'completed');
}
</script>

<template>
    <Head :title="story.title_id" />

    <MobileLayout>
        <div class="flex flex-col">
            <!-- Story Header -->
            <div class="px-4 py-5 border-b">
                <Link href="/" class="inline-flex items-center gap-1 mb-3 text-muted-foreground hover:text-foreground text-sm transition-colors">
                    <ArrowLeft class="size-4" />
                    Kembali
                </Link>

                <h1 class="font-bold text-2xl">
                    {{ story.title_zh }}
                </h1>
                <p class="mt-0.5 text-muted-foreground text-sm">
                    {{ story.title_pinyin }}
                </p>
                <p class="mt-1 text-base">{{ story.title_id }}</p>

                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold text-xs"
                        :class="{
                            'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400': story.hsk_level === 1,
                            'bg-sky-500/15 text-sky-600 dark:text-sky-400': story.hsk_level === 2,
                            'bg-violet-500/15 text-violet-600 dark:text-violet-400': story.hsk_level === 3,
                            'bg-amber-500/15 text-amber-600 dark:text-amber-400': story.hsk_level === 4,
                            'bg-rose-500/15 text-rose-600 dark:text-rose-400': story.hsk_level === 5,
                            'bg-red-500/15 text-red-600 dark:text-red-400': story.hsk_level === 6,
                        }"
                    >
                        HSK {{ story.hsk_level }}
                    </span>
                    <span class="flex items-center gap-1 text-muted-foreground text-xs">
                        <Clock class="size-3" />
                        {{ story.estimated_minutes }} min
                    </span>
                    <span class="flex items-center gap-1 text-muted-foreground text-xs">
                        <BookOpen class="size-3" />
                        {{ story.word_count }} kata
                    </span>
                    <span
                        v-for="cat in story.categories"
                        :key="cat.id"
                        class="bg-muted px-2 py-0.5 rounded-full text-muted-foreground text-xs"
                    >
                        {{ cat.name_id }}
                    </span>
                </div>
            </div>

            <!-- Reader Controls -->
            <ReaderControls
                :show-pinyin="showPinyin"
                :show-translation="showTranslation"
                @toggle-pinyin="showPinyin = !showPinyin"
                @toggle-translation="showTranslation = !showTranslation"
                @increase-font="increaseFontSize"
                @decrease-font="decreaseFontSize"
            />

            <!-- Full Story Text -->
            <div class="space-y-1 px-4 py-4">
                <div v-for="sentence in sentences" :key="sentence.id">
                    <!-- Chinese text with ruby pinyin -->
                    <div :class="showPinyin ? 'leading-[1.75] -mb-[0.2em]' : 'leading-normal'" :style="{ fontSize }">
                        <template v-for="word in sentence.words" :key="word.id">
                            <span v-if="splitPunctuation(word.surface_form).before">{{ splitPunctuation(word.surface_form).before }}</span>
                            <Popover
                                v-if="splitPunctuation(word.surface_form).word"
                                :open="selectedWordId === word.id"
                                @update:open="(open: boolean) => selectedWordId = open ? word.id : null"
                            >
                                <PopoverTrigger as-child>
                                    <ruby
                                        v-if="showPinyin && showPinyinBasedOnLevel(word)"
                                        class="hover:bg-orange-500/10 rounded-lg hover:text-orange-600 dark:hover:text-orange-400 text-center transition-colors cursor-pointer"
                                        :class="{ 'bg-orange-500/10 text-orange-600 dark:text-orange-400': selectedWordId === word.id }"
                                    >
                                        {{ splitPunctuation(word.surface_form).word }}
                                        <rt class="font-normal text-muted-foreground dark:text-white text-center antialiased">
                                            {{ word.dictionary_entry.pinyin }}
                                        </rt>
                                    </ruby>
                                    <span
                                        v-else
                                        class="hover:bg-orange-500/10 rounded-lg hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer"
                                        :class="{ 'bg-orange-500/10 text-orange-600 dark:text-orange-400': selectedWordId === word.id }"
                                    >
                                        {{ splitPunctuation(word.surface_form).word }}
                                    </span>
                                </PopoverTrigger>
                                <WordTooltip
                                    :word="word"
                                    :is-saved="isWordSaved(word)"
                                    :story-id="story.id"
                                    :sentence-id="sentence.id"
                                    @saved="onWordSaved"
                                />
                            </Popover>
                            <span v-if="splitPunctuation(word.surface_form).after">{{ splitPunctuation(word.surface_form).after }}</span>
                        </template>
                    </div>
                    <!-- Per-sentence translation -->
                    <p v-if="showTranslation" class="text-muted-foreground text-sm md:text-base lg:text-lg">
                        {{ sentence.translation_id }}
                    </p>
                </div>
            </div>

            <!-- Done Button -->
            <div v-if="isAuthenticated" class="flex justify-center px-4 pt-2 pb-8">
                <button
                    v-if="!isCompleted"
                    class="inline-flex items-center gap-2 bg-muted hover:bg-emerald-500/15 px-6 py-2.5 rounded-full font-medium text-muted-foreground hover:text-emerald-600 text-sm transition-colors"
                    @click="markComplete"
                >
                    <CheckCircle class="size-4" />
                    Tandai Selesai
                </button>
                <span
                    v-else
                    class="inline-flex items-center gap-2 bg-emerald-500/15 px-6 py-2.5 rounded-full font-medium text-emerald-600 dark:text-emerald-400 text-sm"
                >
                    <CheckCircle class="size-4" />
                    Selesai
                </span>
            </div>
        </div>
    </MobileLayout>
</template>
