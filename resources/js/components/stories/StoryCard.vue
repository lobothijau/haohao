<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, Crown, BookOpen } from 'lucide-vue-next';
import { show } from '@/routes/stories';
import type { Story } from '@/types';

defineProps<{
    story: Story;
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
    <Link :href="show(story).url" class="group block">
        <div class="flex items-center gap-4 rounded-2xl border p-4 transition-all hover:shadow-md hover:border-orange-200 dark:hover:border-orange-800">
            <!-- HSK Level Pill -->
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                :class="hskColors[story.hsk_level] ?? 'bg-muted text-muted-foreground'"
            >
                {{ story.hsk_level }}
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h3 class="truncate text-lg font-bold leading-tight">
                        {{ story.title_zh }}
                    </h3>
                    <Crown
                        v-if="story.is_premium"
                        class="size-4 shrink-0 text-amber-500"
                    />
                </div>
                <p class="text-muted-foreground mt-0.5 truncate text-sm">
                    {{ story.title_id }}
                </p>
                <div class="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
                    <span class="flex items-center gap-1">
                        <Clock class="size-3" />
                        {{ story.estimated_minutes }}m
                    </span>
                    <span class="flex items-center gap-1">
                        <BookOpen class="size-3" />
                        {{ story.word_count }}
                    </span>
                    <span
                        v-for="cat in story.categories.slice(0, 2)"
                        :key="cat.id"
                        class="rounded-full bg-muted px-2 py-0.5"
                    >
                        {{ cat.name_id }}
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>
