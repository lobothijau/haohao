<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, Crown, BookOpen } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { show } from '@/routes/stories';
import type { Story } from '@/types';

defineProps<{
    story: Story;
}>();
</script>

<template>
    <Link :href="show(story).url" class="block">
        <Card
            class="h-full transition-shadow hover:shadow-md"
        >
            <CardHeader>
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="text-xl leading-tight">
                        {{ story.title_zh }}
                    </CardTitle>
                    <div class="flex shrink-0 items-center gap-1">
                        <Badge variant="secondary" class="text-xs">
                            HSK {{ story.hsk_level }}
                        </Badge>
                        <Crown
                            v-if="story.is_premium"
                            class="text-amber-500 size-4"
                        />
                    </div>
                </div>
                <CardDescription>
                    {{ story.title_id }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    class="text-muted-foreground flex items-center gap-3 text-sm"
                >
                    <span class="flex items-center gap-1">
                        <Clock class="size-3.5" />
                        {{ story.estimated_minutes }} min
                    </span>
                    <span class="flex items-center gap-1">
                        <BookOpen class="size-3.5" />
                        {{ story.word_count }} kata
                    </span>
                </div>
                <div
                    v-if="story.categories.length"
                    class="mt-3 flex flex-wrap gap-1"
                >
                    <Badge
                        v-for="category in story.categories"
                        :key="category.id"
                        variant="outline"
                        class="text-xs"
                    >
                        {{ category.name_id }}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
