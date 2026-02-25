<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DeleteUser from '@/components/DeleteUser.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
</script>

<template>
    <MobileLayout>
        <Head title="Delete account" />

        <h1 class="sr-only">Delete Account</h1>

        <SettingsLayout>
            <DeleteUser />
        </SettingsLayout>
    </MobileLayout>
</template>
