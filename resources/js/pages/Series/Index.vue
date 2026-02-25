<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import SeriesCard from '@/components/series/SeriesCard.vue';
import { Input } from '@/components/ui/input';
import { index as seriesIndex } from '@/routes/series';
import type { Series } from '@/types';

type PaginatedSeries = {
    data: Series[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    series: PaginatedSeries;
    filters: {
        hsk_level?: string;
        search?: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const hskLevel = ref(props.filters.hsk_level ?? '');

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters(): void {
    const query: Record<string, string> = {};
    if (search.value) {
        query.search = search.value;
    }
    if (hskLevel.value) {
        query.hsk_level = hskLevel.value;
    }

    router.get(seriesIndex().url, query, {
        preserveState: true,
        replace: true,
    });
}

watch(hskLevel, () => {
    applyFilters();
});

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});
</script>

<template>
    <Head title="Seri Cerita" />

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <!-- Filter Bar -->
            <div class="flex flex-col gap-3">
                <div class="relative">
                    <Search class="top-1/2 left-3 absolute size-4 text-muted-foreground -translate-y-1/2" />
                    <Input v-model="search" placeholder="Cari seri..." class="pl-9 rounded-xl" />
                </div>
                <div class="flex gap-2">
                    <select v-model="hskLevel"
                        class="flex-1 bg-background px-2 border border-input rounded-xl min-w-0 h-10 text-base">
                        <option value="">Semua Level</option>
                        <option v-for="level in 6" :key="level" :value="String(level)">
                            HSK {{ level }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Series Grid -->
            <div v-if="series.data.length" class="flex flex-col gap-3">
                <SeriesCard v-for="item in series.data" :key="item.id" :series="item" />
            </div>
            <div v-else class="flex flex-col justify-center items-center py-16 text-muted-foreground">
                <p class="text-lg">Tidak ada seri ditemukan.</p>
                <p class="text-sm">Coba ubah filter pencarian Anda.</p>
            </div>

            <!-- Pagination -->
            <nav v-if="series.last_page > 1" class="flex justify-center items-center gap-1">
                <template v-for="link in series.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="inline-flex justify-center items-center px-3 border rounded-xl h-9 font-medium text-sm transition-colors"
                        :class="link.active
                                ? 'bg-gradient-to-r from-orange-400 to-pink-500 text-white border-transparent'
                                : 'border-input bg-background hover:bg-accent'
                            " preserve-state v-html="link.label" />
                    <span v-else class="inline-flex justify-center items-center px-3 h-9 text-muted-foreground text-sm"
                        v-html="link.label" />
                </template>
            </nav>
        </div>
    </MobileLayout>
</template>
