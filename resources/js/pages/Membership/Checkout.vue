<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard } from 'lucide-vue-next';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Subscription } from '@/types';

const props = defineProps<{
    subscription: Subscription;
}>();

const form = useForm({});

function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID').format(price);
}

function processPayment(): void {
    form.post(`/membership/checkout/${props.subscription.id}`);
}
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

            <!-- Mock Payment -->
            <Card class="border-dashed">
                <CardContent class="p-4 text-center">
                    <CreditCard class="size-10 mx-auto mb-3 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground mb-4">
                        Ini adalah simulasi pembayaran. Klik tombol di bawah untuk menyelesaikan transaksi.
                    </p>
                    <Button
                        class="w-full"
                        :disabled="form.processing"
                        @click="processPayment"
                    >
                        {{ form.processing ? 'Memproses...' : 'Bayar Sekarang' }}
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
