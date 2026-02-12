<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { BookmarkPlus, BookmarkCheck } from 'lucide-vue-next';
import { PopoverContent } from '@/components/ui/popover';
import { store as vocabularyStore } from '@/routes/vocabulary';
import type { SentenceWord } from '@/types';

const props = defineProps<{
    word: SentenceWord;
    isSaved: boolean;
    storyId: number;
    sentenceId?: number;
}>();

const emit = defineEmits<{
    saved: [dictionaryEntryId: number];
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const hskColors: Record<number, string> = {
    1: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    2: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
    3: 'bg-violet-500/15 text-violet-600 dark:text-violet-400',
    4: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    5: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    6: 'bg-red-500/15 text-red-600 dark:text-red-400',
};

const placeholderExamples = [
    { zh: '我每天都<b>学习</b>中文。', translation: 'Saya belajar bahasa Mandarin setiap hari.' },
    { zh: '他在图书馆<b>学习</b>。', translation: 'Dia belajar di perpustakaan.' },
    { zh: '你喜欢<b>学习</b>什么？', translation: 'Kamu suka belajar apa?' },
];

const scrollContainer = ref<HTMLElement | null>(null);
const activeIndex = ref(0);

function onScroll(): void {
    const el = scrollContainer.value;
    if (!el) {
        return;
    }
    const cardWidth = el.clientWidth;
    activeIndex.value = Math.round(el.scrollLeft / cardWidth);
}

function scrollTo(index: number): void {
    const el = scrollContainer.value;
    if (!el) {
        return;
    }
    el.scrollTo({ left: index * el.clientWidth, behavior: 'smooth' });
}

function saveWord(): void {
    router.post(
        vocabularyStore().url,
        {
            dictionary_entry_id: props.word.dictionary_entry.id,
            source_story_id: props.storyId,
            source_sentence_id: props.sentenceId ?? null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('saved', props.word.dictionary_entry.id);
            },
        },
    );
}
</script>

<template>
    <PopoverContent side="top" align="center" :side-offset="8" class="w-60 rounded-xl border-border/60 bg-popover/95 p-3 shadow-lg backdrop-blur-sm">
        <!-- Character + Pinyin -->
        <div class="text-center">
            <p class="text-2xl font-bold">
                {{ word.dictionary_entry.simplified }}
            </p>
            <p class="text-muted-foreground mt-0.5 text-sm">
                {{ word.dictionary_entry.pinyin }}
                <span v-if="word.dictionary_entry.traditional" class="opacity-60">
                    ({{ word.dictionary_entry.traditional }})
                </span>
            </p>
        </div>

        <!-- Badges -->
        <div v-if="word.dictionary_entry.hsk_level || word.dictionary_entry.word_type" class="mt-2 flex flex-wrap justify-center gap-1.5">
            <span
                v-if="word.dictionary_entry.hsk_level"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="hskColors[word.dictionary_entry.hsk_level] ?? 'bg-muted text-muted-foreground'"
            >
                HSK {{ word.dictionary_entry.hsk_level }}
            </span>
            <span
                v-if="word.dictionary_entry.word_type"
                class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ word.dictionary_entry.word_type }}
            </span>
        </div>

        <!-- Meanings -->
        <div v-if="word.dictionary_entry.meaning_id" class="mt-2 text-sm">
            <span class="text-muted-foreground text-xs">ID</span>
            {{ word.dictionary_entry.meaning_id }}
        </div>

        <div v-if="word.dictionary_entry.meaning_en" class="mt-1 text-sm">
            <span class="text-muted-foreground text-xs">EN</span>
            {{ word.dictionary_entry.meaning_en }}
        </div>

        <!-- Example Sentences -->
        <div class="-mx-3 mt-2 overflow-hidden">
            <div
                ref="scrollContainer"
                class="flex snap-x snap-mandatory overflow-x-auto scrollbar-none"
                @scroll="onScroll"
            >
                <div
                    v-for="(example, i) in placeholderExamples"
                    :key="i"
                    class="min-w-full shrink-0 snap-center px-3"
                >
                    <div class="rounded-lg bg-muted/50 p-2">
                        <p class="text-sm leading-snug [&>b]:font-semibold [&>b]:text-orange-500" v-html="example.zh" />
                        <p class="text-muted-foreground mt-1 text-xs">{{ example.translation }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-1.5 flex justify-center gap-1">
                <button
                    v-for="(_, i) in placeholderExamples"
                    :key="i"
                    class="size-1.5 rounded-full transition-colors"
                    :class="activeIndex === i ? 'bg-foreground' : 'bg-foreground/20'"
                    @click="scrollTo(i)"
                />
            </div>
        </div>

        <!-- Save button -->
        <template v-if="isAuthenticated">
            <button
                v-if="!isSaved"
                class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg bg-foreground px-3 py-1.5 text-sm font-medium text-background transition-opacity hover:opacity-80"
                @click="saveWord"
            >
                <BookmarkPlus class="size-3.5" />
                Simpan
            </button>
            <div
                v-else
                class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-500/15 px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400"
            >
                <BookmarkCheck class="size-3.5" />
                Tersimpan
            </div>
        </template>
    </PopoverContent>
</template>
