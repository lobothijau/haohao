<script setup lang="ts">
import { Eye, EyeOff, Languages, Minus, Plus } from 'lucide-vue-next';

defineProps<{
    showPinyin: boolean;
    showTranslation: boolean;
}>();

defineEmits<{
    'toggle-pinyin': [];
    'toggle-translation': [];
    'increase-font': [];
    'decrease-font': [];
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

        <div class="flex items-center gap-1">
            <button
                class="inline-flex items-center justify-center rounded-full bg-muted size-7 text-muted-foreground transition-colors hover:bg-muted/80"
                @click="$emit('decrease-font')"
            >
                <Minus class="size-3.5" />
            </button>
            <button
                class="inline-flex items-center justify-center rounded-full bg-muted size-7 text-muted-foreground transition-colors hover:bg-muted/80"
                @click="$emit('increase-font')"
            >
                <Plus class="size-3.5" />
            </button>
        </div>
    </div>
</template>
