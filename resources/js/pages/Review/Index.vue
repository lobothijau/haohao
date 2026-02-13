<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { ArrowLeft, RotateCcw, Check } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import type { DictionaryEntry } from '@/types';

type SrsCardItem = {
    id: number;
    dictionary_entry: DictionaryEntry;
    card_state: string;
};

const props = defineProps<{
    dueCount: number;
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
const progress = computed(() => totalDue.value > 0 ? (reviewedCount.value / totalDue.value) * 100 : 0);

async function fetchCards(): Promise<void> {
    loading.value = true;
    try {
        const response = await fetch('/review/cards');
        const data = await response.json();
        cards.value = data.cards;
        currentIndex.value = 0;
        revealed.value = false;

        if (cards.value.length === 0) {
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

onMounted(fetchCards);
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
                <span v-if="totalDue > 0" class="ml-auto text-sm text-muted-foreground">
                    {{ reviewedCount }}/{{ totalDue }}
                </span>
            </div>

            <!-- Progress Bar -->
            <div v-if="totalDue > 0" class="h-1.5 bg-muted rounded-full overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-orange-400 to-pink-500 rounded-full transition-all duration-300"
                    :style="{ width: `${progress}%` }"
                />
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-20">
                <RotateCcw class="size-6 animate-spin text-muted-foreground" />
            </div>

            <!-- Session Done -->
            <div v-else-if="sessionDone" class="flex flex-col items-center justify-center py-16 text-center">
                <div class="flex items-center justify-center size-16 rounded-full bg-emerald-500/15 mb-4">
                    <Check class="size-8 text-emerald-600 dark:text-emerald-400" />
                </div>
                <p class="text-lg font-bold">Selesai!</p>
                <p v-if="reviewedCount > 0" class="text-muted-foreground text-sm mt-1">
                    Kamu sudah meninjau {{ reviewedCount }} kartu.
                </p>
                <p v-else class="text-muted-foreground text-sm mt-1">
                    Tidak ada kartu yang perlu ditinjau sekarang.
                </p>
                <Link href="/" class="mt-4 text-sm text-orange-500 hover:text-orange-600">
                    Kembali ke cerita
                </Link>
            </div>

            <!-- Flashcard -->
            <div v-else-if="currentCard" class="flex flex-col items-center">
                <div
                    class="bg-card border rounded-2xl w-full p-8 flex flex-col items-center justify-center min-h-[280px] cursor-pointer select-none"
                    @click="!revealed && reveal()"
                >
                    <!-- Front: Character -->
                    <p class="font-bold text-5xl mb-4">
                        {{ currentCard.dictionary_entry.simplified }}
                    </p>

                    <!-- Revealed: Pinyin + Meaning -->
                    <template v-if="revealed">
                        <p class="text-muted-foreground text-lg">
                            {{ currentCard.dictionary_entry.pinyin }}
                        </p>
                        <div class="mt-3 text-center border-t pt-3 w-full">
                            <p v-if="currentCard.dictionary_entry.meaning_id" class="text-sm">
                                {{ currentCard.dictionary_entry.meaning_id }}
                            </p>
                            <p v-if="currentCard.dictionary_entry.meaning_en" class="text-xs text-muted-foreground mt-1">
                                {{ currentCard.dictionary_entry.meaning_en }}
                            </p>
                        </div>
                    </template>

                    <!-- Tap hint -->
                    <p v-else class="text-muted-foreground text-xs mt-4">
                        Ketuk untuk melihat jawaban
                    </p>
                </div>

                <!-- Rating buttons (only when revealed) -->
                <div v-if="revealed" class="grid grid-cols-4 gap-2 w-full mt-4">
                    <button
                        class="flex flex-col items-center gap-1 rounded-xl border py-3 text-xs font-medium transition-colors hover:bg-red-500/10 hover:border-red-500/30 hover:text-red-600"
                        @click="rate(1)"
                    >
                        <span class="text-base">😵</span>
                        Lagi
                    </button>
                    <button
                        class="flex flex-col items-center gap-1 rounded-xl border py-3 text-xs font-medium transition-colors hover:bg-amber-500/10 hover:border-amber-500/30 hover:text-amber-600"
                        @click="rate(2)"
                    >
                        <span class="text-base">😰</span>
                        Sulit
                    </button>
                    <button
                        class="flex flex-col items-center gap-1 rounded-xl border py-3 text-xs font-medium transition-colors hover:bg-emerald-500/10 hover:border-emerald-500/30 hover:text-emerald-600"
                        @click="rate(3)"
                    >
                        <span class="text-base">😊</span>
                        Bagus
                    </button>
                    <button
                        class="flex flex-col items-center gap-1 rounded-xl border py-3 text-xs font-medium transition-colors hover:bg-sky-500/10 hover:border-sky-500/30 hover:text-sky-600"
                        @click="rate(4)"
                    >
                        <span class="text-base">😎</span>
                        Mudah
                    </button>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
