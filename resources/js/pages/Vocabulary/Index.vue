<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Trash2, BookOpen, ArrowLeft } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Input } from '@/components/ui/input';
import AddVocabularyDialog from '@/components/vocabulary/AddVocabularyDialog.vue';
import type { UserVocabularyItem } from '@/types';

type PaginatedVocabularies = {
    data: UserVocabularyItem[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    vocabularies: PaginatedVocabularies;
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const query: Record<string, string> = {};
        if (search.value) {
            query.search = search.value;
        }
        router.get('/vocabulary', query, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const hskColors: Record<number, string> = {
    1: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    2: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
    3: 'bg-violet-500/15 text-violet-600 dark:text-violet-400',
    4: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    5: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    6: 'bg-red-500/15 text-red-600 dark:text-red-400',
};

function deleteWord(id: number): void {
    router.delete(`/vocabulary/${id}`, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Kosakata" />

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center gap-2">
                <Link href="/" class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground text-sm transition-colors">
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Kosakata Saya</h1>
            </div>

            <!-- Search -->
            <div class="relative">
                <Search class="top-1/2 left-3 absolute size-4 text-muted-foreground -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Cari kosakata..."
                    class="pl-9 rounded-xl"
                />
            </div>

            <!-- Vocabulary List -->
            <div v-if="vocabularies.data.length" class="flex flex-col gap-2">
                <div
                    v-for="vocab in vocabularies.data"
                    :key="vocab.id"
                    class="flex items-center gap-3 bg-card p-3 border rounded-xl"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-lg">{{ vocab.dictionary_entry.simplified }}</span>
                            <span class="text-muted-foreground text-sm">{{ vocab.dictionary_entry.pinyin }}</span>
                            <span
                                v-if="vocab.dictionary_entry.hsk_level"
                                class="inline-flex items-center px-1.5 py-0.5 rounded-full font-semibold text-[10px]"
                                :class="hskColors[vocab.dictionary_entry.hsk_level] ?? 'bg-muted text-muted-foreground'"
                            >
                                HSK {{ vocab.dictionary_entry.hsk_level }}
                            </span>
                        </div>
                        <p v-if="vocab.dictionary_entry.meaning_id" class="text-muted-foreground text-sm">
                            {{ vocab.dictionary_entry.meaning_id }}
                        </p>
                        <Link
                            v-if="vocab.source_story"
                            :href="`/stories/${vocab.source_story.slug}`"
                            class="inline-flex items-center gap-1 mt-1 text-orange-500 hover:text-orange-600 text-xs"
                        >
                            <BookOpen class="size-3" />
                            {{ vocab.source_story.title_id }}
                        </Link>
                    </div>
                    <button
                        class="hover:bg-red-500/10 p-1.5 rounded-lg text-muted-foreground hover:text-red-500 transition-colors shrink-0"
                        @click="deleteWord(vocab.id)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col justify-center items-center py-16 text-center">
                <BookOpen class="opacity-30 mb-3 size-12 text-muted-foreground" />
                <p class="font-bold text-lg">Belum ada kosakata</p>
                <p class="mt-1 max-w-xs text-muted-foreground text-sm">
                    Baca cerita dan ketuk kata, atau gunakan tombol + untuk menambah kosakata.
                </p>
                <Link href="/" class="inline-flex items-center gap-1.5 mt-4 font-medium text-orange-500 hover:text-orange-600 text-sm">
                    <BookOpen class="size-4" />
                    Jelajahi cerita
                </Link>
            </div>

            <!-- Pagination -->
            <nav v-if="vocabularies.last_page > 1" class="flex justify-center items-center gap-1">
                <template v-for="link in vocabularies.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex justify-center items-center px-3 border rounded-xl h-9 font-medium text-sm transition-colors"
                        :class="link.active
                            ? 'bg-gradient-to-r from-orange-400 to-pink-500 text-white border-transparent'
                            : 'border-input bg-background hover:bg-accent'"
                        preserve-state
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex justify-center items-center px-3 h-9 text-muted-foreground text-sm"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>

        <AddVocabularyDialog />
    </MobileLayout>
</template>
