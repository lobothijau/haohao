<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, Lock, Palette, Shield, User } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { show as showTwoFactor } from '@/routes/two-factor';
import { edit as editAppearance } from '@/routes/appearance';

const items = [
    {
        icon: User,
        title: 'Profile',
        description: 'Name and email address',
        href: editProfile(),
    },
    {
        icon: Lock,
        title: 'Password',
        description: 'Change your password',
        href: editPassword(),
    },
    {
        icon: Shield,
        title: 'Two-Factor',
        description: 'Extra security for your account',
        href: showTwoFactor(),
    },
    {
        icon: Palette,
        title: 'Appearance',
        description: 'Light or dark mode',
        href: editAppearance(),
    },
];
</script>

<template>
    <Head title="Settings" />

    <MobileLayout>
        <div class="flex flex-col gap-4 p-4">
            <div class="flex items-center gap-2">
                <Link href="/" class="inline-flex items-center text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Settings</h1>
            </div>

            <nav class="flex flex-col gap-1">
                <Link
                    v-for="item in items"
                    :key="item.title"
                    :href="item.href"
                    class="flex items-center gap-3 hover:bg-accent px-3 py-2.5 rounded-xl transition-colors"
                >
                    <component :is="item.icon" class="size-5 text-muted-foreground shrink-0" />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium">{{ item.title }}</div>
                        <div class="text-xs text-muted-foreground">{{ item.description }}</div>
                    </div>
                    <ChevronRight class="size-4 text-muted-foreground shrink-0" />
                </Link>
            </nav>
        </div>
    </MobileLayout>
</template>
