<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { Menu, LogOut, Settings, Sun, Moon, X, BookOpen, GraduationCap, BarChart3, Layers, Crown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import UserInfo from '@/components/UserInfo.vue';
import { useAppearance } from '@/composables/useAppearance';
import { login, logout, register } from '@/routes';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const isPremium = computed(() => page.props.auth?.is_premium ?? false);
const menuOpen = ref(false);
const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

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
    <header class="top-0 z-50 sticky bg-background/95 supports-[backdrop-filter]:bg-background/80 backdrop-blur border-b">
        <div class="flex justify-between items-center mx-auto px-4 max-w-xl h-14">
            <div class="flex items-center gap-3">
                <Link href="/" class="flex items-center gap-2.5" @click="closeMenu">
                    <div class="flex justify-center items-center bg-gradient-to-br from-orange-400 to-pink-500 shadow-sm px-2 rounded-xl">
                        <span class="font-bold text-white text-lg">好好</span>
                    </div>
                </Link>
                <Link v-if="!isPremium" href="/membership" class="flex items-center gap-1 hover:bg-accent px-2.5 py-1.5 rounded-xl text-sm font-medium transition-colors" @click="closeMenu">
                    <Crown class="size-4 text-amber-500" />
                    <span>Premium</span>
                </Link>
            </div>

            <div class="flex items-center gap-1">
                <Button variant="ghost" size="icon" class="rounded-xl" @click="toggleTheme">
                    <component :is="resolvedAppearance === 'dark' ? Sun : Moon" class="size-5" />
                </Button>
                <Button variant="ghost" size="icon" class="rounded-xl" @click="toggleMenu">
                    <component :is="menuOpen ? X : Menu" class="size-5" />
                </Button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div v-if="menuOpen" class="px-4 pb-4 border-b">
            <nav class="flex flex-col gap-1 mx-auto max-w-xl">
                <template v-if="user">
                    <div class="flex items-center gap-2 px-2 py-2">
                        <UserInfo :user="user" :show-email="true" />
                    </div>
                    <div class="my-1 border-t" />
                    <Link
                        href="/vocabulary"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl text-sm"
                        @click="closeMenu"
                    >
                        <BookOpen class="size-4" />
                        Kosakata
                    </Link>
                    <Link
                        href="/series"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl text-sm"
                        @click="closeMenu"
                    >
                        <Layers class="size-4" />
                        Seri
                    </Link>
                    <Link
                        href="/review"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl text-sm"
                        @click="closeMenu"
                    >
                        <GraduationCap class="size-4" />
                        Latihan
                    </Link>
                    <Link
                        href="/stats"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl text-sm"
                        @click="closeMenu"
                    >
                        <BarChart3 class="size-4" />
                        Statistik
                    </Link>
                    <div class="my-1 border-t" />
                    <Link
                        href="/settings"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl text-sm"
                        @click="closeMenu"
                    >
                        <Settings class="size-4" />
                        Settings
                    </Link>
                    <Link
                        :href="logout()"
                        as="button"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl w-full text-sm"
                        @click="handleLogout"
                    >
                        <LogOut class="size-4" />
                        Log out
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="flex items-center gap-2 hover:bg-accent px-3 py-2.5 rounded-xl font-medium text-sm"
                        @click="closeMenu"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="flex items-center gap-2 bg-gradient-to-r from-orange-400 to-pink-500 px-3 py-2.5 rounded-xl font-medium text-white text-sm"
                        @click="closeMenu"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>
