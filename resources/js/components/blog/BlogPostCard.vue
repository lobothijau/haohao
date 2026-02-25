<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import { show } from '@/routes/blog';
import type { BlogPost } from '@/types';

defineProps<{
    post: BlogPost;
}>();

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '';
    }
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Link :href="show(post).url" class="group block">
        <div class="overflow-hidden rounded-2xl border bg-card transition-all hover:shadow-md hover:border-orange-200 dark:hover:border-orange-800">
            <div v-if="post.featured_image_url" class="aspect-video w-full overflow-hidden bg-muted">
                <img
                    :src="post.featured_image_url"
                    :alt="post.title"
                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                />
            </div>
            <div v-else class="flex aspect-video w-full items-center justify-center bg-gradient-to-br from-orange-50 to-pink-50 dark:from-orange-950/20 dark:to-pink-950/20">
                <span class="text-4xl text-orange-300 dark:text-orange-700">Blog</span>
            </div>
            <div class="p-4">
                <span
                    v-if="post.category"
                    class="inline-flex items-center rounded-full bg-orange-500/10 px-2.5 py-0.5 text-xs font-medium text-orange-600 dark:text-orange-400"
                >
                    {{ post.category.name }}
                </span>
                <h3 class="mt-2 text-lg font-bold leading-tight line-clamp-2">
                    {{ post.title }}
                </h3>
                <p v-if="post.excerpt" class="mt-1.5 text-sm text-muted-foreground line-clamp-2">
                    {{ post.excerpt }}
                </p>
                <div class="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Calendar class="size-3" />
                    {{ formatDate(post.published_at) }}
                </div>
            </div>
        </div>
    </Link>
</template>
