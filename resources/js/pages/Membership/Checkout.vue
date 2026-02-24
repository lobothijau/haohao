<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, AlertTriangle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Subscription } from '@/types';

const props = defineProps<{
    subscription: Subscription;
    snapToken: string;
    clientKey: string;
    isProduction: boolean;
}>();

type PaymentStatus = 'idle' | 'success' | 'pending' | 'error';

const paymentStatus = ref<PaymentStatus>('idle');

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID').format(price);
}

function openSnapPayment(): void {
    if (!window.snap) {
        paymentStatus.value = 'error';
        return;
    }

    window.snap.pay(props.snapToken, {
        onSuccess() {
            paymentStatus.value = 'success';
            setTimeout(() => {
                router.visit('/membership');
            }, 2000);
        },
        onPending() {
            paymentStatus.value = 'pending';
        },
        onError() {
            paymentStatus.value = 'error';
        },
        onClose() {
            if (paymentStatus.value === 'idle') {
                paymentStatus.value = 'idle';
            }
        },
    });
}

onMounted(() => {
    openSnapPayment();
});
</script>

<template>
    <Head title="Pembayaran" />

    <MobileLayout>
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-2">
                <Link
                    href="/membership"
                    class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground text-sm transition-colors"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <h1 class="font-bold text-xl">Pembayaran</h1>
            </div>

            <!-- Order Summary -->
            <Card>
                <CardContent class="p-4">
                    <h2 class="font-semibold mb-4">Ringkasan Pesanan</h2>
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Order ID</span>
                            <span class="font-mono text-xs">{{ subscription.midtrans_order_id }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Paket</span>
                            <span class="font-medium">{{ subscription.plan?.label }}</span>
                        </div>
                        <div class="border-t my-1" />
                        <div class="flex justify-between">
                            <span class="font-semibold">Total</span>
                            <span class="font-bold text-lg">Rp{{ formatPrice(subscription.amount) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Status -->
            <Card v-if="paymentStatus === 'success'" class="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                <CardContent class="p-4 text-center">
                    <CheckCircle class="size-10 mx-auto mb-3 text-green-600 dark:text-green-400" />
                    <p class="font-semibold text-green-800 dark:text-green-200">Pembayaran berhasil!</p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">Mengalihkan ke halaman membership...</p>
                </CardContent>
            </Card>

            <Card v-else-if="paymentStatus === 'pending'" class="border-yellow-200 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950">
                <CardContent class="p-4 text-center">
                    <Clock class="size-10 mx-auto mb-3 text-yellow-600 dark:text-yellow-400" />
                    <p class="font-semibold text-yellow-800 dark:text-yellow-200">Menunggu pembayaran</p>
                    <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-1">Silakan selesaikan pembayaran Anda. Kami akan mengaktifkan akun Anda setelah pembayaran dikonfirmasi.</p>
                    <Button class="mt-4 w-full" variant="outline" @click="router.visit('/membership')">
                        Kembali ke Membership
                    </Button>
                </CardContent>
            </Card>

            <Card v-else-if="paymentStatus === 'error'" class="border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950">
                <CardContent class="p-4 text-center">
                    <AlertTriangle class="size-10 mx-auto mb-3 text-red-600 dark:text-red-400" />
                    <p class="font-semibold text-red-800 dark:text-red-200">Pembayaran gagal</p>
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.</p>
                    <Button class="mt-4 w-full" @click="openSnapPayment">
                        Coba Lagi
                    </Button>
                </CardContent>
            </Card>

            <Card v-else>
                <CardContent class="p-4 text-center">
                    <p class="text-sm text-muted-foreground mb-4">
                        Klik tombol di bawah untuk membuka halaman pembayaran.
                    </p>
                    <Button class="w-full" @click="openSnapPayment">
                        Bayar Sekarang
                    </Button>
                </CardContent>
            </Card>

            <!-- Cancel -->
            <div class="text-center">
                <Link
                    href="/membership"
                    class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                    Batalkan dan kembali
                </Link>
            </div>
        </div>
    </MobileLayout>
</template>
