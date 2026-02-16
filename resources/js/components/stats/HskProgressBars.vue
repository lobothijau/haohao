<script setup lang="ts">
import type { HskLevelProgress } from '@/types/stats';

defineProps<{
    data: HskLevelProgress[];
}>();

const levelColors: Record<number, string> = {
    1: 'bg-emerald-500',
    2: 'bg-sky-500',
    3: 'bg-violet-500',
    4: 'bg-amber-500',
    5: 'bg-rose-500',
    6: 'bg-red-500',
};
</script>

<template>
    <div class="flex flex-col gap-3">
        <div v-for="item in data" :key="item.level" class="flex flex-col gap-1">
            <div class="flex justify-between items-center">
                <span class="font-medium text-sm">HSK {{ item.level }}</span>
                <span class="text-muted-foreground text-xs">{{ item.learned }} / {{ item.total }} kata</span>
            </div>
            <div class="bg-muted rounded-full h-2 overflow-hidden">
                <div
                    class="rounded-full h-full transition-all duration-500"
                    :class="levelColors[item.level] ?? 'bg-muted-foreground'"
                    :style="{ width: item.total > 0 ? `${(item.learned / item.total) * 100}%` : '0%' }"
                />
            </div>
        </div>
    </div>
</template>
