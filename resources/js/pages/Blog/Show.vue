<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, User } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import BlogPostCard from '@/components/blog/BlogPostCard.vue';
import { index as blogIndex } from '@/routes/blog';
import type { BlogPost, SeoMeta } from '@/types';

defineProps<{
    post: BlogPost;
    relatedPosts: BlogPost[];
    seo: SeoMeta;
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
    <Head :title="seo.title">
        <meta v-if="seo.description" name="description" :content="seo.description" />
        <meta property="og:title" :content="seo.title" />
        <meta v-if="seo.description" property="og:description" :content="seo.description" />
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta property="og:url" :content="seo.url" />
        <meta property="og:type" :content="seo.type" />
        <meta v-if="seo.published_time" property="article:published_time" :content="seo.published_time" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.title" />
        <meta v-if="seo.description" name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" name="twitter:image" :content="seo.image" />
    </Head>

    <MobileLayout>
        <div class="flex flex-col">
            <!-- Header -->
            <div class="px-4 py-5 border-b">
                <Link :href="blogIndex().url" class="inline-flex items-center gap-1 mb-3 text-sm text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="size-4" />
                    Blog
                </Link>

                <span
                    v-if="post.category"
                    class="inline-flex items-center rounded-full bg-orange-500/10 px-2.5 py-0.5 text-xs font-medium text-orange-600 dark:text-orange-400 mb-2"
                >
                    {{ post.category.name }}
                </span>

                <h1 class="text-2xl font-bold leading-tight">{{ post.title }}</h1>

                <div class="mt-3 flex items-center gap-4 text-sm text-muted-foreground">
                    <span v-if="post.creator" class="flex items-center gap-1.5">
                        <User class="size-3.5" />
                        {{ post.creator.name }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Calendar class="size-3.5" />
                        {{ formatDate(post.published_at) }}
                    </span>
                </div>
            </div>

            <!-- Featured Image -->
            <div v-if="post.featured_image_url" class="w-full">
                <img
                    :src="post.featured_image_url"
                    :alt="post.title"
                    class="w-full object-cover"
                />
            </div>

            <!-- Body -->
            <div class="prose prose-sm dark:prose-invert max-w-none px-4 py-6" v-html="post.body" />

            <!-- Related Posts -->
            <div v-if="relatedPosts.length" class="border-t px-4 py-6">
                <h2 class="mb-4 text-lg font-bold">Artikel Terkait</h2>
                <div class="grid grid-cols-1 gap-4">
                    <BlogPostCard v-for="related in relatedPosts" :key="related.id" :post="related" />
                </div>
            </div>
        </div>
    </MobileLayout>
</template>
