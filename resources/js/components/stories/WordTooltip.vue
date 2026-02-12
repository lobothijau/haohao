<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { BookmarkPlus, BookmarkCheck } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { PopoverContent } from '@/components/ui/popover';
import { store as vocabularyStore } from '@/routes/vocabulary';
import type { SentenceWord } from '@/types';

const props = defineProps<{
    word: SentenceWord;
    isSaved: boolean;
    storyId: number;
    sentenceId?: number;
}>();

const emit = defineEmits<{
    saved: [dictionaryEntryId: number];
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

function saveWord(): void {
    router.post(
        vocabularyStore().url,
        {
            dictionary_entry_id: props.word.dictionary_entry.id,
            source_story_id: props.storyId,
            source_sentence_id: props.sentenceId ?? null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('saved', props.word.dictionary_entry.id);
            },
        },
    );
}
</script>

<template>
    <PopoverContent side="top" align="center" :side-offset="8" class="w-64 p-3">
        <div class="flex flex-col gap-2">
            <div class="text-center">
                <p class="text-2xl font-bold">
                    {{ word.dictionary_entry.simplified }}
                </p>
                <p class="text-muted-foreground text-sm">
                    {{ word.dictionary_entry.pinyin }}
                    <span v-if="word.dictionary_entry.traditional" class="ml-1">
                        ({{ word.dictionary_entry.traditional }})
                    </span>
                </p>
            </div>

            <div class="flex flex-wrap justify-center gap-1">
                <Badge v-if="word.dictionary_entry.hsk_level" variant="secondary" class="text-xs">
                    HSK {{ word.dictionary_entry.hsk_level }}
                </Badge>
                <Badge v-if="word.dictionary_entry.word_type" variant="outline" class="text-xs">
                    {{ word.dictionary_entry.word_type }}
                </Badge>
            </div>

            <Separator />

            <div v-if="word.dictionary_entry.meaning_id" class="space-y-0.5">
                <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                    Arti (ID)
                </p>
                <p class="text-sm">{{ word.dictionary_entry.meaning_id }}</p>
            </div>

            <div v-if="word.dictionary_entry.meaning_en" class="space-y-0.5">
                <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                    Meaning (EN)
                </p>
                <p class="text-sm">{{ word.dictionary_entry.meaning_en }}</p>
            </div>

            <template v-if="isAuthenticated">
                <Separator />
                <Button v-if="!isSaved" size="sm" class="w-full" @click="saveWord">
                    <BookmarkPlus class="mr-1.5 size-3.5" />
                    Simpan
                </Button>
                <Button v-else variant="secondary" size="sm" class="w-full" disabled>
                    <BookmarkCheck class="mr-1.5 size-3.5" />
                    Tersimpan
                </Button>
            </template>
        </div>
    </PopoverContent>
</template>
