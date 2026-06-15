<script setup lang="ts">
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import type { SummaryPeriod } from '@/types';

defineProps<{
    modelValue: SummaryPeriod;
}>();

const emit = defineEmits<{
    'update:modelValue': [period: SummaryPeriod];
}>();

const PERIODS: { key: SummaryPeriod; label: string }[] = [
    { key: 'this_week',  label: 'This Week' },
    { key: 'this_month', label: 'This Month' },
    { key: 'all_time',   label: 'All Time' },
];
</script>

<template>
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <button v-for="p in PERIODS" :key="p.key" type="button" :class="[
                'px-4 py-2 rounded-full text-sm font-medium border transition-colors',
                modelValue === p.key
                    ? 'bg-sidebar text-white border-sidebar'
                    : 'bg-white text-text-secondary border-border-light hover:bg-gray-50',
            ]" @click="emit('update:modelValue', p.key)">
                {{ p.label }}
            </button>
        </div>

        <button type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border border-border-light bg-white text-text-secondary hover:bg-gray-50 transition-colors">
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export PDF
        </button>
    </div>
</template>
