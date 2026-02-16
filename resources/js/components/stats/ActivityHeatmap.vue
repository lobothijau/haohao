<script setup lang="ts">
import { computed } from 'vue';
import type { DayActivity } from '@/types/stats';

const props = defineProps<{
    data: DayActivity[];
}>();

const maxCount = computed(() => Math.max(...props.data.map(d => d.count), 1));

function getIntensityClass(count: number): string {
    if (count === 0) {
        return 'bg-muted';
    }
    const ratio = count / maxCount.value;
    if (ratio <= 0.25) {
        return 'bg-orange-200 dark:bg-orange-900';
    }
    if (ratio <= 0.5) {
        return 'bg-orange-300 dark:bg-orange-700';
    }
    if (ratio <= 0.75) {
        return 'bg-orange-400 dark:bg-orange-500';
    }
    return 'bg-orange-500 dark:bg-orange-400';
}

const weeks = computed(() => {
    const result: DayActivity[][] = [];
    for (let i = 0; i < props.data.length; i += 7) {
        result.push(props.data.slice(i, i + 7));
    }
    return result;
});

const dayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
</script>

<template>
    <div class="overflow-x-auto">
        <div class="flex gap-0.5">
            <!-- Day labels column -->
            <div class="flex flex-col gap-0.5 pr-1">
                <div
                    v-for="(label, i) in dayLabels"
                    :key="i"
                    class="flex items-center justify-end h-3 text-[10px] text-muted-foreground leading-none"
                >
                    {{ i % 2 === 0 ? label : '' }}
                </div>
            </div>
            <!-- Heatmap grid -->
            <div v-for="(week, wi) in weeks" :key="wi" class="flex flex-col gap-0.5">
                <div
                    v-for="(day, di) in week"
                    :key="di"
                    class="rounded-sm size-3 transition-colors"
                    :class="getIntensityClass(day.count)"
                    :title="`${day.date}: ${day.count} aktivitas`"
                />
            </div>
        </div>
    </div>
</template>
