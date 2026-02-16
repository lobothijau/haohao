<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import { ArrowLeft, Flame, BookOpen, BookCheck, Target, Activity } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import ActivityHeatmap from '@/components/stats/ActivityHeatmap.vue';
import HskProgressBars from '@/components/stats/HskProgressBars.vue';
import type { ReviewAccuracy, DayActivity, HskLevelProgress } from '@/types/stats';

defineProps<{
    streakCount: number;
    streakLastDate: string | null;
    hskLevel: number;
    totalWordsLearned: number;
    totalStoriesRead: number;
    reviewAccuracy?: ReviewAccuracy;
    weeklyActivity?: DayActivity[];
    hskProgress?: HskLevelProgress[];
}>();
</script>

<template>
    <Head title="Statistik" />

    <MobileLayout>
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-2">
                <Link href="/" class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground text-sm transition-colors">
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Statistik</h1>
            </div>

            <!-- Section 1: Overview Cards -->
            <div class="gap-3 grid grid-cols-3">
                <Card class="py-4">
                    <CardContent class="flex flex-col items-center gap-1 px-3 py-0">
                        <div class="flex justify-center items-center rounded-full size-10"
                            :class="streakCount > 0 ? 'bg-orange-500/15' : 'bg-muted'">
                            <Flame class="size-5" :class="streakCount > 0 ? 'text-orange-500' : 'text-muted-foreground'" />
                        </div>
                        <span class="font-bold text-2xl">{{ streakCount }}</span>
                        <span class="text-muted-foreground text-xs text-center">Hari Beruntun</span>
                    </CardContent>
                </Card>

                <Card class="py-4">
                    <CardContent class="flex flex-col items-center gap-1 px-3 py-0">
                        <div class="flex justify-center items-center bg-sky-500/15 rounded-full size-10">
                            <BookOpen class="size-5 text-sky-500" />
                        </div>
                        <span class="font-bold text-2xl">{{ totalWordsLearned }}</span>
                        <span class="text-muted-foreground text-xs text-center">Kata Dipelajari</span>
                    </CardContent>
                </Card>

                <Card class="py-4">
                    <CardContent class="flex flex-col items-center gap-1 px-3 py-0">
                        <div class="flex justify-center items-center bg-emerald-500/15 rounded-full size-10">
                            <BookCheck class="size-5 text-emerald-500" />
                        </div>
                        <span class="font-bold text-2xl">{{ totalStoriesRead }}</span>
                        <span class="text-muted-foreground text-xs text-center">Cerita Selesai</span>
                    </CardContent>
                </Card>
            </div>

            <!-- Section 2: Review Accuracy (deferred) -->
            <Card>
                <CardContent class="px-4 py-0">
                    <div class="flex items-center gap-2 mb-3">
                        <Target class="size-4 text-muted-foreground" />
                        <h2 class="font-semibold text-sm">Akurasi Review</h2>
                    </div>

                    <Deferred :data="['reviewAccuracy']">
                        <template #fallback>
                            <div class="flex gap-4">
                                <Skeleton class="h-16 w-full rounded-xl" />
                                <Skeleton class="h-16 w-full rounded-xl" />
                            </div>
                        </template>

                        <div v-if="reviewAccuracy" class="gap-4 grid grid-cols-2">
                            <div class="bg-muted/50 p-3 rounded-xl text-center">
                                <p class="font-bold text-2xl">{{ reviewAccuracy.all_time }}%</p>
                                <p class="text-muted-foreground text-xs">Keseluruhan</p>
                            </div>
                            <div class="bg-muted/50 p-3 rounded-xl text-center">
                                <p class="font-bold text-2xl">{{ reviewAccuracy.last_7_days }}%</p>
                                <p class="text-muted-foreground text-xs">7 Hari Terakhir</p>
                            </div>
                        </div>
                        <div v-else class="bg-muted/50 p-4 rounded-xl text-center">
                            <p class="text-muted-foreground text-sm">Belum ada data review</p>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>

            <!-- Section 3: Activity Heatmap (deferred) -->
            <Card>
                <CardContent class="px-4 py-0">
                    <div class="flex items-center gap-2 mb-3">
                        <Activity class="size-4 text-muted-foreground" />
                        <h2 class="font-semibold text-sm">Aktivitas 12 Minggu</h2>
                    </div>

                    <Deferred :data="['weeklyActivity']">
                        <template #fallback>
                            <Skeleton class="h-28 w-full rounded-xl" />
                        </template>

                        <ActivityHeatmap v-if="weeklyActivity" :data="weeklyActivity" />
                    </Deferred>
                </CardContent>
            </Card>

            <!-- Section 4: HSK Progress (deferred) -->
            <Card>
                <CardContent class="px-4 py-0">
                    <div class="flex items-center gap-2 mb-3">
                        <BookOpen class="size-4 text-muted-foreground" />
                        <h2 class="font-semibold text-sm">Progres HSK</h2>
                    </div>

                    <Deferred :data="['hskProgress']">
                        <template #fallback>
                            <div class="flex flex-col gap-3">
                                <Skeleton v-for="i in 6" :key="i" class="h-8 w-full rounded-lg" />
                            </div>
                        </template>

                        <HskProgressBars v-if="hskProgress" :data="hskProgress" />
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </MobileLayout>
</template>
