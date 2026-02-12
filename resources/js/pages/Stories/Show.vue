<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Clock, BookOpen, ArrowLeft } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverTrigger } from '@/components/ui/popover';
import ReaderControls from '@/components/stories/ReaderControls.vue';
import WordTooltip from '@/components/stories/WordTooltip.vue';
import { progress as progressRoute } from '@/routes/stories';
import type {
    Story,
    StorySentence,
    SentenceWord,
    ReadingProgress,
} from '@/types';

const props = defineProps<{
    story: Story;
    sentences: StorySentence[];
    progress: ReadingProgress | null;
    savedVocabularyIds: number[];
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const showPinyin = ref(true);
const showTranslation = ref(false);
const selectedWordId = ref<number | null>(null);
const savedIds = ref<Set<number>>(new Set(props.savedVocabularyIds));
const isCompleted = ref(props.progress?.status === 'completed');

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
                <Button variant="ghost" size="sm" class="mb-3 -ml-2" as-child>
                    <a href="/">
                        <ArrowLeft class="mr-1 size-4" />
                        Kembali
                    </a>
                </Button>

                <h1 class="font-bold text-2xl">
                    {{ story.title_zh }}
                </h1>
                <p class="mt-0.5 text-muted-foreground text-sm">
                    {{ story.title_pinyin }}
                </p>
                <p class="mt-1 text-base">{{ story.title_id }}</p>

                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <Badge variant="secondary">
                        HSK {{ story.hsk_level }}
                    </Badge>
                    <span class="flex items-center gap-1 text-muted-foreground text-xs">
                        <Clock class="size-3" />
                        {{ story.estimated_minutes }} min
                    </span>
                    <span class="flex items-center gap-1 text-muted-foreground text-xs">
                        <BookOpen class="size-3" />
                        {{ story.word_count }} kata
                    </span>
                    <Badge
                        v-for="cat in story.categories"
                        :key="cat.id"
                        variant="outline"
                        class="text-xs"
                    >
                        {{ cat.name_id }}
                    </Badge>
                </div>
            </div>

            <!-- Reader Controls -->
            <ReaderControls
                :show-pinyin="showPinyin"
                :show-translation="showTranslation"
                :is-completed="isCompleted"
                :is-authenticated="isAuthenticated"
                @toggle-pinyin="showPinyin = !showPinyin"
                @toggle-translation="showTranslation = !showTranslation"
                @mark-complete="markComplete"
            />

            <!-- Full Story Text -->
            <div class="space-y-3 px-4 py-4">
                <div v-for="sentence in sentences" :key="sentence.id" :class="showPinyin ? 'leading-[3.5]' : 'leading-loose'">
                    <!-- Chinese text with ruby pinyin -->
                    <div class="text-xl">
                        <template v-for="word in sentence.words" :key="word.id">
                            <span v-if="splitPunctuation(word.surface_form).before">{{ splitPunctuation(word.surface_form).before }}</span>
                            <Popover
                                v-if="splitPunctuation(word.surface_form).word"
                                :open="selectedWordId === word.id"
                                @update:open="(open: boolean) => selectedWordId = open ? word.id : null"
                            >
                                <PopoverTrigger as-child>
                                    <ruby
                                        class="hover:bg-primary/10 rounded hover:text-primary transition-colors cursor-pointer text-center"
                                        :class="{ 'bg-primary/10 text-primary': selectedWordId === word.id }"
                                    >
                                        {{ splitPunctuation(word.surface_form).word }}
                                        <rt v-if="showPinyin" class="font-normal text-white text-center">
                                            {{ word.dictionary_entry.pinyin }}
                                        </rt>
                                        <rt v-else />
                                    </ruby>
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
                    <p v-if="showTranslation" class="mt-1 text-muted-foreground text-sm">
                        {{ sentence.translation_id }}
                    </p>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
