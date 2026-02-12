<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { Menu, LogOut, Settings, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import UserInfo from '@/components/UserInfo.vue';
import { login, logout, register } from '@/routes';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const menuOpen = ref(false);

function toggleMenu(): void {
    menuOpen.value = !menuOpen.value;
}

function closeMenu(): void {
    menuOpen.value = false;
}

function handleLogout(): void {
    router.flushAll();
    closeMenu();
}
</script>

<template>
    <header class="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky top-0 z-50 border-b backdrop-blur">
        <div class="mx-auto flex h-14 max-w-xl items-center justify-between px-4">
            <Link href="/" class="flex items-center gap-2" @click="closeMenu">
                <div class="flex size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
                </div>
                <span class="text-sm font-semibold">NiHao</span>
            </Link>

            <Button variant="ghost" size="icon" @click="toggleMenu">
                <component :is="menuOpen ? X : Menu" class="size-5" />
            </Button>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div v-if="menuOpen" class="border-b px-4 pb-4">
            <nav class="mx-auto flex max-w-xl flex-col gap-1">
                <template v-if="user">
                    <div class="flex items-center gap-2 px-2 py-2">
                        <UserInfo :user="user" :show-email="true" />
                    </div>
                    <div class="my-1 border-t" />
                    <Link
                        :href="edit()"
                        class="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-sm"
                        @click="closeMenu"
                    >
                        <Settings class="size-4" />
                        Settings
                    </Link>
                    <Link
                        :href="logout()"
                        as="button"
                        class="hover:bg-accent flex w-full items-center gap-2 rounded-md px-2 py-2 text-sm"
                        @click="handleLogout"
                    >
                        <LogOut class="size-4" />
                        Log out
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-sm"
                        @click="closeMenu"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="hover:bg-accent flex items-center gap-2 rounded-md px-2 py-2 text-sm"
                        @click="closeMenu"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>
