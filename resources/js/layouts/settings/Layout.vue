<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center gap-2">
            <Link
                href="/settings"
                class="text-muted-foreground hover:text-foreground inline-flex items-center transition-colors"
            >
                <ArrowLeft class="size-4" />
            </Link>
            <h1 class="text-xl font-bold">Settings</h1>
        </div>

        <div class="flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                <slot />
            </section>
        </div>
    </div>
</template>
