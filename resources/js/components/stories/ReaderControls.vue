<script setup lang="ts">
import { Eye, EyeOff, Languages, CheckCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

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
        <div class="flex items-center gap-1">
            <Button
                variant="ghost"
                size="sm"
                :class="showPinyin ? 'text-primary' : 'text-muted-foreground'"
                @click="$emit('toggle-pinyin')"
            >
                <Languages class="mr-1 size-4" />
                Pinyin
            </Button>
            <Button
                variant="ghost"
                size="sm"
                :class="showTranslation ? 'text-primary' : 'text-muted-foreground'"
                @click="$emit('toggle-translation')"
            >
                <component
                    :is="showTranslation ? Eye : EyeOff"
                    class="mr-1 size-4"
                />
                Terjemahan
            </Button>
        </div>

        <div v-if="isAuthenticated" class="flex items-center">
            <Button
                v-if="!isCompleted"
                variant="outline"
                size="sm"
                @click="$emit('mark-complete')"
            >
                <CheckCircle class="mr-1 size-4" />
                Selesai
            </Button>
            <span
                v-else
                class="text-sm font-medium text-green-600 dark:text-green-400"
            >
                <CheckCircle class="mr-1 inline size-4" />
                Selesai
            </span>
        </div>
    </div>
</template>
