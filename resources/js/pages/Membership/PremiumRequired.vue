<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock, Crown } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    story: {
        id: number;
        title_zh: string;
        title_id: string;
        slug: string;
        hsk_level: number;
    };
}>();
</script>

<template>
    <Head :title="story.title_id" />

    <MobileLayout>
        <div class="flex flex-col items-center justify-center gap-6 p-8 text-center min-h-[60vh]">
            <div class="flex items-center justify-center rounded-full bg-amber-500/15 size-20">
                <Lock class="size-10 text-amber-500" />
            </div>

            <div class="flex flex-col gap-2">
                <h1 class="text-xl font-bold">{{ story.title_zh }}</h1>
                <p class="text-muted-foreground text-sm">{{ story.title_id }}</p>
            </div>

            <p class="text-muted-foreground text-sm max-w-xs">
                Cerita ini membutuhkan akses premium. Upgrade akunmu untuk membaca semua cerita HSK 2-6.
            </p>

            <Link href="/membership">
                <Button class="gap-2">
                    <Crown class="size-4" />
                    Lihat Paket Premium
                </Button>
            </Link>

            <Link
                href="/"
                class="text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
                Kembali ke beranda
            </Link>
        </div>
    </MobileLayout>
</template>
