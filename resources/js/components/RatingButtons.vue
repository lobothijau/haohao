<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    disabled?: boolean;
}>();

const emit = defineEmits<{
    rate: [rating: number];
}>();

const ratings = [
    { value: 1, emoji: '😵', label: 'Ulangi', color: 'hover:bg-red-500/10 hover:border-red-500/30 hover:text-red-600' },
    { value: 2, emoji: '😰', label: 'Lupa', color: 'hover:bg-amber-500/10 hover:border-amber-500/30 hover:text-amber-600' },
    { value: 3, emoji: '😊', label: 'Ingat', color: 'hover:bg-emerald-500/10 hover:border-emerald-500/30 hover:text-emerald-600' },
    { value: 4, emoji: '😎', label: 'Gampang', color: 'hover:bg-sky-500/10 hover:border-sky-500/30 hover:text-sky-600' },
];

function onKeydown(e: KeyboardEvent): void {
    if (props.disabled) {
        return;
    }

    const rating = parseInt(e.key);
    if (rating >= 1 && rating <= 4) {
        emit('rate', rating);
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="gap-2 grid grid-cols-4 w-full">
        <button
            v-for="r in ratings"
            :key="r.value"
            :disabled="disabled"
            class="relative flex flex-col items-center gap-1 disabled:opacity-50 py-3 border rounded-xl font-medium text-xs transition-colors"
            :class="r.color"
            @click="emit('rate', r.value)"
        >
            <span class="top-1 left-2 absolute text-[10px] text-muted-foreground/50">{{ r.value }}</span>
            <span class="text-base">{{ r.emoji }}</span>
            {{ r.label }}
        </button>
    </div>
</template>
