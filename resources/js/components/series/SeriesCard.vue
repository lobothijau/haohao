<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Layers } from 'lucide-vue-next';
import { show } from '@/routes/series';
import type { Series } from '@/types';

defineProps<{
    series: Series;
}>();

const hskColors: Record<number, string> = {
    1: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    2: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
    3: 'bg-violet-500/15 text-violet-600 dark:text-violet-400',
    4: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    5: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    6: 'bg-red-500/15 text-red-600 dark:text-red-400',
};
</script>

<template>
    <Link :href="show(series).url" class="group block">
        <div class="flex items-center gap-4 rounded-2xl border bg-gradient-to-r from-orange-50/50 to-pink-50/50 dark:from-orange-950/20 dark:to-pink-950/20 p-4 transition-all hover:shadow-md hover:border-orange-200 dark:hover:border-orange-800">
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-xl"
                :class="hskColors[series.hsk_level] ?? 'bg-muted text-muted-foreground'"
            >
                <Layers class="size-5" />
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="truncate text-lg font-bold leading-tight">
                        {{ series.title_zh }}
                    </h3>
                </div>
                <p class="text-muted-foreground mt-0.5 truncate text-sm">
                    {{ series.title_id }}
                </p>
                <div class="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold"
                        :class="hskColors[series.hsk_level] ?? 'bg-muted text-muted-foreground'"
                    >
                        HSK {{ series.hsk_level }}
                    </span>
                    <span class="flex items-center gap-1">
                        <Layers class="size-3" />
                        {{ series.stories_count ?? 0 }} bab
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>
