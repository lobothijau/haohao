<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ArrowLeft, RotateCcw, Check, BookOpen, Sparkles, Volume2 } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import RatingButtons from '@/components/RatingButtons.vue';
import { trackEvent } from '@/composables/useAnalytics';
import type { DictionaryEntry } from '@/types';

type SrsCardItem = {
    id: number;
    dictionary_entry: DictionaryEntry;
    card_state: string;
    card_type: 'recognition' | 'recall' | 'listening';
};

const props = defineProps<{
    dueCount: number;
    totalCardCount: number;
}>();

const cards = ref<SrsCardItem[]>([]);
const currentIndex = ref(0);
const revealed = ref(false);
const reviewedCount = ref(0);
const totalDue = ref(props.dueCount);
const loading = ref(false);
const sessionDone = ref(false);
const reviewStartTime = ref<number>(0);

const currentCard = computed(() => cards.value[currentIndex.value] ?? null);
const isListeningCard = computed(() => currentCard.value?.card_type === 'listening');
const examples = computed(() => currentCard.value?.dictionary_entry.examples ?? []);
const progress = computed(() => totalDue.value > 0 ? (reviewedCount.value / totalDue.value) * 100 : 0);

function playCurrentCardAudio(): void {
    const audioUrl = currentCard.value?.dictionary_entry.audio_url;
    if (audioUrl) {
        new Audio(audioUrl).play();
    }
}

watch(currentCard, (card) => {
    if (card?.card_type === 'listening' && card.dictionary_entry.audio_url) {
        setTimeout(() => playCurrentCardAudio(), 200);
    }
});

async function fetchCards(): Promise<void> {
    loading.value = true;
    try {
        const response = await fetch('/review/cards');
        const data = await response.json();
        cards.value = data.cards;
        currentIndex.value = 0;
        revealed.value = false;

        if (cards.value.length > 0 && reviewedCount.value === 0) {
            trackEvent('review_start', { due_count: totalDue.value });
        }

        if (cards.value.length === 0) {
            if (reviewedCount.value > 0) {
                trackEvent('review_complete', { reviewed_count: reviewedCount.value });
            }
            sessionDone.value = true;
        }
    } finally {
        loading.value = false;
    }
}

function reveal(): void {
    revealed.value = true;
    reviewStartTime.value = Date.now();
}

async function rate(rating: number): Promise<void> {
    if (!currentCard.value) {
        return;
    }

    const timeTaken = Date.now() - reviewStartTime.value;

    await fetch(`/review/${currentCard.value.id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            ),
        },
        body: JSON.stringify({
            rating,
            time_taken_ms: timeTaken,
        }),
    });

    reviewedCount.value++;
    revealed.value = false;

    if (currentIndex.value + 1 < cards.value.length) {
        currentIndex.value++;
    } else {
        await fetchCards();
    }
}

function onKeydown(e: KeyboardEvent): void {
    if ((e.key === ' ' || e.key === 'Enter') && !revealed.value && currentCard.value) {
        e.preventDefault();
        reveal();
    }
}

onMounted(() => {
    fetchCards();
    window.addEventListener('keydown', onKeydown);
});
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head title="Latihan" />

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center gap-2">
                <Link href="/" class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground text-sm transition-colors">
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Latihan</h1>
                <span v-if="totalDue > 0" class="ml-auto text-muted-foreground text-sm">
                    {{ reviewedCount }}/{{ totalDue }}
                </span>
            </div>

            <!-- Progress Bar -->
            <div v-if="totalDue > 0" class="bg-muted rounded-full h-1.5 overflow-hidden">
                <div
                    class="bg-gradient-to-r from-orange-400 to-pink-500 rounded-full h-full transition-all duration-300"
                    :style="{ width: `${progress}%` }"
                />
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center py-20">
                <RotateCcw class="size-6 text-muted-foreground animate-spin" />
            </div>

            <!-- First-time Empty State (no cards at all) -->
            <div v-else-if="sessionDone && totalCardCount === 0 && reviewedCount === 0" class="flex flex-col justify-center items-center py-16 text-center">
                <div class="flex justify-center items-center bg-orange-500/15 mb-4 rounded-full size-16">
                    <Sparkles class="size-8 text-orange-500" />
                </div>
                <p class="font-bold text-lg">Belum ada kartu latihan</p>
                <p class="mt-1 text-muted-foreground text-sm max-w-xs">
                    Simpan kata baru dari cerita ke kosakata, lalu kartu latihan SRS akan otomatis dibuat.
                </p>
                <div class="mt-4 flex flex-col items-center gap-2">
                    <Link href="/" class="inline-flex items-center gap-1.5 text-orange-500 hover:text-orange-600 text-sm font-medium">
                        <BookOpen class="size-4" />
                        Jelajahi cerita
                    </Link>
                    <Link href="/vocabulary" class="text-muted-foreground hover:text-foreground text-xs">
                        Lihat kosakata
                    </Link>
                </div>
            </div>

            <!-- Session Done (has cards, all reviewed) -->
            <div v-else-if="sessionDone" class="flex flex-col justify-center items-center py-16 text-center">
                <div class="flex justify-center items-center bg-emerald-500/15 mb-4 rounded-full size-16">
                    <Check class="size-8 text-emerald-600 dark:text-emerald-400" />
                </div>
                <p class="font-bold text-lg">Selesai!</p>
                <p v-if="reviewedCount > 0" class="mt-1 text-muted-foreground text-sm">
                    Kamu sudah meninjau {{ reviewedCount }} kartu.
                </p>
                <p v-else class="mt-1 text-muted-foreground text-sm">
                    Tidak ada kartu yang perlu ditinjau sekarang.
                </p>
                <Link href="/" class="mt-4 text-orange-500 hover:text-orange-600 text-sm">
                    Kembali ke cerita
                </Link>
            </div>

            <!-- Flashcard -->
            <div v-else-if="currentCard" class="flex flex-col items-center">
                <div
                    class="flex flex-col justify-center items-center bg-card p-8 border rounded-2xl w-full min-h-[280px] cursor-pointer select-none"
                    @click="!revealed && reveal()"
                >
                    <!-- Listening Card Front -->
                    <template v-if="isListeningCard && !revealed">
                        <div class="flex justify-center items-center bg-emerald-500/15 mb-4 rounded-full size-20">
                            <Volume2 class="size-10 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <button
                            class="inline-flex items-center gap-1.5 bg-muted hover:bg-muted/80 px-4 py-2 rounded-full text-sm font-medium text-muted-foreground transition-colors"
                            @click.stop="playCurrentCardAudio()"
                        >
                            <Volume2 class="size-4" />
                            Putar ulang
                        </button>
                        <p class="mt-4 text-muted-foreground text-xs">
                            Dengarkan dan coba ingat artinya
                        </p>
                    </template>

                    <!-- Recognition Card Front -->
                    <template v-else-if="!revealed">
                        <p class="mb-4 font-bold text-5xl">
                            {{ currentCard.dictionary_entry.simplified }}
                        </p>
                        <p class="mt-4 text-muted-foreground text-xs">
                            Tekan spasi, enter, klik atau sentuh untuk melihat jawaban
                        </p>
                    </template>

                    <!-- Revealed: Pinyin + Meaning (shared by all card types) -->
                    <template v-if="revealed">
                        <p class="mb-4 font-bold text-5xl">
                            {{ currentCard.dictionary_entry.simplified }}
                        </p>
                        <p class="text-muted-foreground text-lg">
                            {{ currentCard.dictionary_entry.pinyin }}
                        </p>
                        <div class="mt-3 pt-3 border-t w-full text-center">
                            <p v-if="currentCard.dictionary_entry.meaning_id" class="text-sm">
                                {{ currentCard.dictionary_entry.meaning_id }}
                            </p>
                            <p v-if="currentCard.dictionary_entry.meaning_en" class="mt-1 text-muted-foreground text-xs">
                                {{ currentCard.dictionary_entry.meaning_en }}
                            </p>
                        </div>

                        <!-- Example Sentences -->
                        <div v-if="examples.length" class="mt-3 pt-3 border-t w-full">
                            <p class="text-muted-foreground text-[10px] uppercase tracking-wider mb-2">Contoh</p>
                            <div v-for="(example, i) in examples.slice(0, 2)" :key="i" class="bg-muted/50 rounded-lg p-2" :class="{ 'mt-2': i > 0 }">
                                <p class="text-sm leading-snug">{{ example.sentence_zh }}</p>
                                <p v-if="example.sentence_pinyin" class="mt-0.5 text-muted-foreground text-xs">{{ example.sentence_pinyin }}</p>
                                <p v-if="example.sentence_id" class="mt-0.5 text-muted-foreground text-xs">{{ example.sentence_id }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Rating buttons (only when revealed) -->
                <RatingButtons v-if="revealed" class="mt-4" @rate="rate" />
            </div>
        </div>
    </MobileLayout>
</template>
