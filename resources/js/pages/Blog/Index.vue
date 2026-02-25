<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import BlogPostCard from '@/components/blog/BlogPostCard.vue';
import { Input } from '@/components/ui/input';
import { index as blogIndex } from '@/routes/blog';
import type { BlogCategory, BlogPost } from '@/types';

type PaginatedPosts = {
    data: BlogPost[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    posts: PaginatedPosts;
    categories: BlogCategory[];
    filters: {
        category?: string;
        search?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const activeCategory = ref(props.filters.category ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters(): void {
    const query: Record<string, string> = {};
    if (search.value) {
        query.search = search.value;
    }
    if (activeCategory.value) {
        query.category = activeCategory.value;
    }

    router.get(blogIndex().url, query, {
        preserveState: true,
        replace: true,
    });
}

watch(activeCategory, () => {
    applyFilters();
});

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});
</script>

<template>
    <Head title="Blog">
        <meta name="description" content="Tips, panduan, dan artikel seputar belajar bahasa Mandarin untuk pemula hingga mahir." />
    </Head>

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold">Blog</h1>
                <p class="mt-1 text-sm text-muted-foreground">Tips dan panduan belajar bahasa Mandarin</p>
            </div>

            <!-- Filter Bar -->
            <div class="flex flex-col gap-3">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari artikel..." class="rounded-xl pl-9" />
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="!activeCategory
                            ? 'bg-gradient-to-r from-orange-400 to-pink-500 text-white'
                            : 'bg-muted text-muted-foreground hover:text-foreground'"
                        @click="activeCategory = ''"
                    >
                        Semua
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="activeCategory === cat.slug
                            ? 'bg-gradient-to-r from-orange-400 to-pink-500 text-white'
                            : 'bg-muted text-muted-foreground hover:text-foreground'"
                        @click="activeCategory = cat.slug"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <!-- Posts Grid -->
            <div v-if="posts.data.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BlogPostCard v-for="post in posts.data" :key="post.id" :post="post" />
            </div>
            <div v-else class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                <p class="text-lg">Tidak ada artikel ditemukan.</p>
                <p class="text-sm">Coba ubah filter pencarian Anda.</p>
            </div>

            <!-- Pagination -->
            <nav v-if="posts.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in posts.links" :key="link.label">
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
                        class="inline-flex h-9 items-center justify-center px-3 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </MobileLayout>
</template>
