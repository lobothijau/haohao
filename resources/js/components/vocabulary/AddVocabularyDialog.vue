<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus, Loader2 } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const open = ref(false);

const form = ref({
    simplified: '',
    pinyin: '',
    meaning_id: '',
    meaning_en: '',
});
const errors = ref<Record<string, string>>({});
const submitting = ref(false);

function getCsrfToken(): string {
    return decodeURIComponent(
        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
    );
}

async function submit(): Promise<void> {
    submitting.value = true;
    errors.value = {};

    try {
        const response = await fetch('/vocabulary/custom', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });

        if (!response.ok) {
            const data = await response.json();
            if (data.errors) {
                const parsed: Record<string, string> = {};
                for (const [key, messages] of Object.entries(data.errors)) {
                    parsed[key] = (messages as string[])[0];
                }
                errors.value = parsed;
            }
            return;
        }

        form.value = { simplified: '', pinyin: '', meaning_id: '', meaning_en: '' };
        errors.value = {};
        router.reload({ only: ['vocabularies'] });
    } finally {
        submitting.value = false;
    }
}

watch(open, (isOpen) => {
    if (!isOpen) {
        form.value = { simplified: '', pinyin: '', meaning_id: '', meaning_en: '' };
        errors.value = {};
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <div class="fixed bottom-0 left-0 right-0 z-40 pointer-events-none">
            <div class="mx-auto max-w-xl relative">
                <DialogTrigger as-child>
                    <button
                        class="absolute bottom-6 right-6 pointer-events-auto flex size-14 items-center justify-center rounded-full bg-gradient-to-r from-orange-400 to-pink-500 text-white shadow-lg transition-transform hover:scale-105 active:scale-95"
                    >
                        <Plus class="size-6" />
                    </button>
                </DialogTrigger>
            </div>
        </div>

        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Tambah Kosakata</DialogTitle>
            </DialogHeader>

            <div class="flex flex-col gap-4">
                <div>
                    <label class="text-sm font-medium mb-1.5 block">Karakter (Hanzi)</label>
                    <Input
                        v-model="form.simplified"
                        placeholder="例如: 你好"
                        class="rounded-xl"
                    />
                    <p v-if="errors.simplified" class="text-destructive text-xs mt-1">{{ errors.simplified }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium mb-1.5 block">Pinyin</label>
                    <Input
                        v-model="form.pinyin"
                        placeholder="例如: nǐ hǎo"
                        class="rounded-xl"
                    />
                    <p v-if="errors.pinyin" class="text-destructive text-xs mt-1">{{ errors.pinyin }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium mb-1.5 block">Arti (Indonesia)</label>
                    <Input
                        v-model="form.meaning_id"
                        placeholder="例如: halo, selamat"
                        class="rounded-xl"
                    />
                    <p v-if="errors.meaning_id" class="text-destructive text-xs mt-1">{{ errors.meaning_id }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium mb-1.5 block">Meaning (English) <span class="text-muted-foreground font-normal">- opsional</span></label>
                    <Input
                        v-model="form.meaning_en"
                        placeholder="e.g. hello"
                        class="rounded-xl"
                    />
                    <p v-if="errors.meaning_en" class="text-destructive text-xs mt-1">{{ errors.meaning_en }}</p>
                </div>

                <Button
                    :disabled="submitting"
                    class="w-full rounded-xl bg-gradient-to-r from-orange-400 to-pink-500 text-white hover:from-orange-500 hover:to-pink-600 border-0"
                    @click="submit"
                >
                    <Loader2 v-if="submitting" class="size-4 animate-spin" />
                    <template v-else>Simpan Kosakata</template>
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
