<script setup lang="ts">
import { Eye, EyeOff, Languages, CheckCircle } from 'lucide-vue-next';

defineProps<{
    showPinyin: boolean;
    showTranslation: boolean;
    isCompleted: boolean;
    isAuthenticated: boolean;
}>();

defineEmits<{
    'toggle-pinyin': [];
    'toggle-translation': [];
    'mark-complete': [];
}>();
</script>

<template>
    <div
        class="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky top-14 z-10 flex items-center justify-between gap-2 border-b px-4 py-2 backdrop-blur"
    >
        <div class="flex items-center gap-1.5">
            <button
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                :class="showPinyin
                    ? 'bg-orange-500/15 text-orange-600 dark:text-orange-400'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                @click="$emit('toggle-pinyin')"
            >
                <Languages class="size-3.5" />
                Pinyin
            </button>
            <button
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                :class="showTranslation
                    ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                @click="$emit('toggle-translation')"
            >
                <component :is="showTranslation ? Eye : EyeOff" class="size-3.5" />
                Terjemahan
            </button>
        </div>

        <div v-if="isAuthenticated" class="flex items-center">
            <button
                v-if="!isCompleted"
                class="inline-flex items-center gap-1.5 rounded-full bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-emerald-500/15 hover:text-emerald-600"
                @click="$emit('mark-complete')"
            >
                <CheckCircle class="size-3.5" />
                Selesai
            </button>
            <span
                v-else
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
            >
                <CheckCircle class="size-3.5" />
                Selesai
            </span>
        </div>
    </div>
</template>
