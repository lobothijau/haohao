<script setup lang="ts">
import MobileHeader from '@/components/MobileHeader.vue';
</script>

<template>
    <div class="bg-background min-h-screen">
        <MobileHeader />
        <main class="mx-auto max-w-xl">
            <slot />
        </main>
    </div>
</template>
