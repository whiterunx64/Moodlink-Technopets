<script setup lang="ts">
import { ArrowRightStartOnRectangleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import SettingsSection from '@/Components/UI/SettingsSection.vue';

defineEmits<{
    logout: [];
    deleteAccount: [];
}>();
</script>

<template>
    <SettingsSection title="Danger Zone" description="Irreversible actions — proceed with caution" :danger="true">
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="button" @click="$emit('logout')"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                <ArrowRightStartOnRectangleIcon class="w-4 h-4" /> Log Out
            </button>
            <button type="button" @click="$emit('deleteAccount')"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-red-500 border border-red-200 rounded-xl hover:bg-red-500 hover:text-white transition-all">
                <TrashIcon class="w-4 h-4" /> Delete Account
            </button>
        </div>
    </SettingsSection>
</template>
