<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Crown, Check, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { Plan, Subscription } from '@/types';

const props = defineProps<{
    plans: Plan[];
    activeSubscription: Subscription | null;
}>();

const founderPlan = computed(() => props.plans.find((p) => p.slug === 'founder'));
const regularPlans = computed(() => props.plans.filter((p) => p.slug !== 'founder'));

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const user = computed(() => page.props.auth?.user ?? null);

const founderCounter = computed(() => page.props.founderCounter as { claimed: number; limit: number });
const founderRemaining = computed(() => founderCounter.value.limit - founderCounter.value.claimed);
const founderPercentage = computed(() => Math.round((founderCounter.value.claimed / founderCounter.value.limit) * 100));
const founderUrgencyColor = computed(() => {
    if (founderPercentage.value >= 90) return 'red';
    if (founderPercentage.value >= 70) return 'amber';
    return 'green';
});

function selectPlan(planSlug: string): void {
    if (!user.value) {
        router.visit('/register');
        return;
    }
    const form = useForm({ plan: planSlug });
    form.post('/membership/subscribe');
}

function pricePerMonth(plan: Plan): number | null {
    if (plan.duration_months <= 1) {
        return null;
    }
    return Math.round(plan.price / plan.duration_months);
}

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID').format(price);
}
</script>

<template>
    <Head title="Membership" />

    <MobileLayout>
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-2">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground text-sm transition-colors"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Membership</h1>
            </div>

            <!-- Success Flash -->
            <div
                v-if="flash?.success"
                class="bg-emerald-50 dark:bg-emerald-950 p-4 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-200 text-sm"
            >
                <div class="flex items-center gap-2">
                    <Check class="size-4 shrink-0" />
                    {{ flash.success }}
                </div>
            </div>

            <!-- Error Flash -->
            <div
                v-if="flash?.error"
                class="bg-red-50 dark:bg-red-950 p-4 border border-red-200 dark:border-red-800 rounded-xl text-red-800 dark:text-red-200 text-sm"
            >
                <div class="flex items-center gap-2">
                    <span class="size-4 shrink-0 font-bold">!</span>
                    {{ flash.error }}
                </div>
            </div>

            <!-- Active Subscription -->
            <Card v-if="activeSubscription" class="border-emerald-200 dark:border-emerald-800">
                <CardContent class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <Crown class="size-5 text-amber-500" />
                        <h2 class="font-semibold">Premium Aktif</h2>
                    </div>
                    <p class="text-muted-foreground text-sm">
                        Berlaku sampai {{ new Date(activeSubscription.expires_at!).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </p>
                </CardContent>
            </Card>

            <!-- Founder Card -->
            <button
                v-if="!activeSubscription && founderPlan"
                class="group relative bg-gradient-to-br from-amber-500 via-orange-500 to-pink-500 p-[1px] rounded-2xl w-full overflow-hidden text-left cursor-pointer"
                @click="selectPlan(founderPlan.slug)"
            >
                <div class="bg-background group-hover:bg-accent/50 p-5 rounded-[15px] transition-colors">
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2">
                            <div class="bg-gradient-to-br from-amber-500 to-pink-500 px-2 py-0.5 rounded-lg">
                                <span class="font-bold text-white text-xs tracking-wide">FOUNDER EDITION</span>
                            </div>
                            <Sparkles class="size-4 text-amber-500" />
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                    </div>
                    <div class="mb-3">
                        <span class="font-bold text-3xl">Rp{{ formatPrice(founderPlan.price) }}</span>
                        <span class="text-muted-foreground text-sm"> {{ formatPrice(pricePerMonth(founderPlan)!) }} / bulan</span>
                    </div>

                    <!-- Founder Counter -->
                    <div class="mb-3">
                        <div class="bg-muted h-2 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="{
                                    'bg-gradient-to-r from-emerald-400 to-emerald-500': founderUrgencyColor === 'green',
                                    'bg-gradient-to-r from-amber-400 to-amber-500': founderUrgencyColor === 'amber',
                                    'bg-gradient-to-r from-red-400 to-red-500': founderUrgencyColor === 'red',
                                }"
                                :style="{ width: `${Math.min(founderPercentage, 100)}%` }"
                            />
                        </div>
                        <div class="flex justify-between items-center mt-1.5">
                            <span
                                class="font-medium text-xs"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400': founderUrgencyColor === 'green',
                                    'text-amber-600 dark:text-amber-400': founderUrgencyColor === 'amber',
                                    'text-red-600 dark:text-red-400': founderUrgencyColor === 'red',
                                }"
                            >
                                {{ founderRemaining }} slot tersisa
                            </span>
                            <span class="text-muted-foreground text-xs">{{ founderCounter.claimed }}/{{ founderCounter.limit }}</span>
                        </div>
                    </div>

                    <p class="text-muted-foreground text-sm">
                        Harga spesial untuk pendukung awal. Akses premium selama {{ founderPlan.duration_months }} bulan.
                    </p>
                </div>
            </button>

            <!-- Regular Plans -->
            <div v-if="!activeSubscription" class="flex flex-col gap-3">
                <h2 class="font-semibold text-muted-foreground text-sm">Paket Lainnya</h2>

                <button
                    v-for="plan in regularPlans"
                    :key="plan.slug"
                    class="w-full text-left cursor-pointer"
                    @click="selectPlan(plan.slug)"
                >
                    <Card class="group relative hover:bg-accent/50 transition-colors overflow-hidden">
                        <div v-if="plan.slug === 'yearly'" class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-bl-lg">
                            PALING HEMAT
                        </div>
                        <CardContent class="flex justify-between items-center p-4">
                            <div class="flex flex-col gap-0.5">
                                <h3 class="font-semibold">{{ plan.label }}</h3>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-bold text-lg">Rp{{ formatPrice(plan.price) }}</span>
                                    <span v-if="pricePerMonth(plan)" class="text-muted-foreground text-xs">
                                        Rp{{ formatPrice(pricePerMonth(plan)!) }}/bulan
                                    </span>
                                </div>
                            </div>
                            <ArrowRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 shrink-0" />
                        </CardContent>
                    </Card>
                </button>
            </div>

            <!-- Features List -->
            <Card>
                <CardContent class="p-4">
                    <h2 class="mb-3 font-semibold">Keuntungan Premium</h2>
                    <div class="flex flex-col gap-2.5">
                        <div class="flex items-start gap-2 text-sm">
                            <Check class="mt-0.5 size-4 text-emerald-500 shrink-0" />
                            <span>Akses semua cerita HSK 2-6</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm">
                            <Check class="mt-0.5 size-4 text-emerald-500 shrink-0" />
                            <span>Cerita baru setiap minggu</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm">
                            <Check class="mt-0.5 size-4 text-emerald-500 shrink-0" />
                            <span>Latihan SRS tanpa batas</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm">
                            <Check class="mt-0.5 size-4 text-emerald-500 shrink-0" />
                            <span>Mendukung pengembangan haohao.com</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </MobileLayout>
</template>
