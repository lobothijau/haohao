<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import StoryCard from '@/components/stories/StoryCard.vue';
import { Input } from '@/components/ui/input';
import type { Story, Category } from '@/types';

type PaginatedStories = {
    data: Story[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    stories: PaginatedStories;
    categories: Category[];
    filters: {
        hsk_level?: string;
        category?: string;
        search?: string;
        sort?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const hskLevel = ref(props.filters.hsk_level ?? '');
const category = ref(props.filters.category ?? '');
const sort = ref(props.filters.sort ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters(): void {
    const query: Record<string, string> = {};
    if (search.value) {
        query.search = search.value;
    }
    if (hskLevel.value) {
        query.hsk_level = hskLevel.value;
    }
    if (category.value) {
        query.category = category.value;
    }
    if (sort.value) {
        query.sort = sort.value;
    }

    router.get('/', query, {
        preserveState: true,
        replace: true,
    });
}

watch([hskLevel, category, sort], () => {
    applyFilters();
});

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});
</script>

<template>
    <Head title="Stories" />

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <!-- Filter Bar -->
            <div class="flex flex-col gap-3">
                <div class="relative">
                    <Search
                        class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari cerita..."
                        class="pl-9"
                    />
                </div>
                <div class="flex gap-2">
                    <select
                        v-model="hskLevel"
                        class="border-input bg-background ring-offset-background h-9 flex-1 rounded-md border px-3 text-sm"
                    >
                        <option value="">Semua HSK</option>
                        <option
                            v-for="level in 6"
                            :key="level"
                            :value="String(level)"
                        >
                            HSK {{ level }}
                        </option>
                    </select>
                    <select
                        v-model="category"
                        class="border-input bg-background ring-offset-background h-9 flex-1 rounded-md border px-3 text-sm"
                    >
                        <option value="">Semua Kategori</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.slug"
                        >
                            {{ cat.name_id }}
                        </option>
                    </select>
                    <select
                        v-model="sort"
                        class="border-input bg-background ring-offset-background h-9 flex-1 rounded-md border px-3 text-sm"
                    >
                        <option value="">Terbaru</option>
                        <option value="hsk_level">HSK Level</option>
                        <option value="difficulty_score">Kesulitan</option>
                    </select>
                </div>
            </div>

            <!-- Story Grid -->
            <div
                v-if="stories.data.length"
                class="flex flex-col gap-3"
            >
                <StoryCard
                    v-for="story in stories.data"
                    :key="story.id"
                    :story="story"
                />
            </div>
            <div
                v-else
                class="text-muted-foreground flex flex-col items-center justify-center py-16"
            >
                <p class="text-lg">Tidak ada cerita ditemukan.</p>
                <p class="text-sm">Coba ubah filter pencarian Anda.</p>
            </div>

            <!-- Pagination -->
            <nav
                v-if="stories.last_page > 1"
                class="flex items-center justify-center gap-1"
            >
                <template v-for="link in stories.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="border-input inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-background hover:bg-accent'
                        "
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
