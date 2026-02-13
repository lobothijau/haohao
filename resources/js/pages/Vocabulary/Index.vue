<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Trash2, BookOpen, ArrowLeft } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Input } from '@/components/ui/input';
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
                <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Cari kosakata..."
                    class="rounded-xl pl-9"
                />
            </div>

            <!-- Vocabulary List -->
            <div v-if="vocabularies.data.length" class="flex flex-col gap-2">
                <div
                    v-for="vocab in vocabularies.data"
                    :key="vocab.id"
                    class="flex items-center gap-3 bg-card border rounded-xl p-3"
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
                        <p v-if="vocab.dictionary_entry.meaning_id" class="text-sm text-muted-foreground truncate">
                            {{ vocab.dictionary_entry.meaning_id }}
                        </p>
                        <Link
                            v-if="vocab.source_story"
                            :href="`/stories/${vocab.source_story.slug}`"
                            class="inline-flex items-center gap-1 mt-1 text-xs text-orange-500 hover:text-orange-600"
                        >
                            <BookOpen class="size-3" />
                            {{ vocab.source_story.title_id }}
                        </Link>
                    </div>
                    <button
                        class="text-muted-foreground hover:text-red-500 p-1.5 rounded-lg hover:bg-red-500/10 transition-colors shrink-0"
                        @click="deleteWord(vocab.id)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                <BookOpen class="size-12 mb-3 opacity-30" />
                <p class="text-lg">Belum ada kosakata.</p>
                <p class="text-sm">Simpan kata dari cerita untuk mulai belajar.</p>
            </div>

            <!-- Pagination -->
            <nav v-if="vocabularies.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in vocabularies.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex h-9 items-center justify-center rounded-xl border px-3 text-sm font-medium transition-colors"
                        :class="link.active
                            ? 'bg-gradient-to-r from-orange-400 to-pink-500 text-white border-transparent'
                            : 'border-input bg-background hover:bg-accent'"
                        preserve-state
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="text-muted-foreground inline-flex h-9 items-center justify-center px-3 text-sm"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </MobileLayout>
</template>
