<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, Lock, Palette, Shield, Trash2, User } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { show as showTwoFactor } from '@/routes/two-factor';
import { edit as editAppearance } from '@/routes/appearance';
import { show as showDeleteAccount } from '@/routes/delete-account';

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
    {
        icon: Trash2,
        title: 'Delete Account',
        description: 'Permanently delete your account',
        href: showDeleteAccount(),
        destructive: true,
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
                    v-for="item in items.filter(i => !i.destructive)"
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

            <nav class="flex flex-col gap-1 border-t pt-3">
                <Link
                    v-for="item in items.filter(i => i.destructive)"
                    :key="item.title"
                    :href="item.href"
                    class="flex items-center gap-3 hover:bg-red-50 dark:hover:bg-red-950/30 px-3 py-2.5 rounded-xl transition-colors"
                >
                    <component :is="item.icon" class="size-5 text-red-500 shrink-0" />
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-red-600 dark:text-red-400">{{ item.title }}</div>
                        <div class="text-xs text-red-400 dark:text-red-500">{{ item.description }}</div>
                    </div>
                    <ChevronRight class="size-4 text-red-400 shrink-0" />
                </Link>
            </nav>
        </div>
    </MobileLayout>
</template>
