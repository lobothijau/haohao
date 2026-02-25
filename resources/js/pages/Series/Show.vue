<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Layers, Clock, BookOpen, CheckCircle } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { show as storyShow } from '@/routes/stories';
import { index as seriesIndex } from '@/routes/series';
import type { Series, Story, ReadingProgress } from '@/types';

const props = defineProps<{
    series: Series;
    stories: Story[];
    chapterProgress: Record<number, ReadingProgress>;
}>();

const hskColors: Record<number, string> = {
    1: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    2: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
    3: 'bg-violet-500/15 text-violet-600 dark:text-violet-400',
    4: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    5: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    6: 'bg-red-500/15 text-red-600 dark:text-red-400',
};

function isChapterCompleted(storyId: number): boolean {
    return props.chapterProgress[storyId]?.status === 'completed';
}
</script>

<template>
    <Head :title="series.title_id" />

    <MobileLayout>
        <div class="flex flex-col">
            <!-- Header -->
            <div class="px-4 py-5 border-b">
                <Link :href="seriesIndex().url" class="inline-flex items-center gap-1 mb-3 text-muted-foreground hover:text-foreground text-sm transition-colors">
                    <ArrowLeft class="size-4" />
                    Seri
                </Link>

                <h1 class="font-bold text-2xl">
                    {{ series.title_zh }}
                </h1>
                <p class="mt-0.5 text-muted-foreground text-sm">
                    {{ series.title_pinyin }}
                </p>
                <p class="mt-1 text-base">{{ series.title_id }}</p>

                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold text-xs"
                        :class="hskColors[series.hsk_level] ?? 'bg-muted text-muted-foreground'"
                    >
                        HSK {{ series.hsk_level }}
                    </span>
                    <span class="flex items-center gap-1 text-muted-foreground text-xs">
                        <Layers class="size-3" />
                        {{ stories.length }} bab
                    </span>
                </div>

                <p v-if="series.description_id" class="mt-3 text-muted-foreground text-sm">
                    {{ series.description_id }}
                </p>
            </div>

            <!-- Chapter List -->
            <div class="flex flex-col gap-3 p-4">
                <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wider">Daftar Bab</h2>
                <Link
                    v-for="(story, idx) in stories"
                    :key="story.id"
                    :href="storyShow(story).url"
                    class="group block"
                >
                    <div class="flex items-center gap-4 rounded-2xl border p-4 transition-all hover:shadow-md hover:border-orange-200 dark:hover:border-orange-800"
                        :class="isChapterCompleted(story.id) ? 'bg-emerald-500/5' : ''"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground font-bold text-sm">
                            {{ story.series_order ?? idx + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="truncate font-bold leading-tight">
                                {{ story.title_zh }}
                            </h3>
                            <p class="text-muted-foreground mt-0.5 truncate text-sm">
                                {{ story.title_id }}
                            </p>
                            <div class="mt-1.5 flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Clock class="size-3" />
                                    {{ story.estimated_minutes }}m
                                </span>
                                <span class="flex items-center gap-1">
                                    <BookOpen class="size-3" />
                                    {{ story.word_count }} kata
                                </span>
                            </div>
                        </div>
                        <CheckCircle
                            v-if="isChapterCompleted(story.id)"
                            class="size-5 shrink-0 text-emerald-500"
                        />
                    </div>
                </Link>

                <div v-if="!stories.length" class="flex flex-col justify-center items-center py-12 text-muted-foreground">
                    <p class="text-sm">Belum ada bab dalam seri ini.</p>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
