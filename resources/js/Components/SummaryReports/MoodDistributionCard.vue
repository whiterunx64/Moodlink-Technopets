<script setup lang="ts">
import type { MoodDistributionItem, SummaryPeriod } from '@/types';

defineProps<{
    distribution: MoodDistributionItem[];
    period: SummaryPeriod;
}>();

const MOOD_STYLE: Record<string, { bar: string; text: string; dot: string }> = {
    Excited:  { bar: 'bg-green-500',  text: 'text-green-600',  dot: 'bg-green-500' },
    Content:  { bar: 'bg-blue-500',   text: 'text-blue-600',   dot: 'bg-blue-500' },
    Stressed: { bar: 'bg-orange-400', text: 'text-orange-500', dot: 'bg-orange-400' },
    Drained:  { bar: 'bg-red-400',    text: 'text-red-500',    dot: 'bg-red-400' },
};

const PERIOD_LABEL: Record<SummaryPeriod, string> = {
    this_week:  'this week',
    this_month: 'this month',
    all_time:   'all time',
};
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-text-primary">Mood Distribution</h3>
            <p class="text-xs text-text-muted mt-0.5">
                All sections — {{ PERIOD_LABEL[period] }}
            </p>
        </div>

        <!-- Stacked bar -->
        <div class="flex h-4 rounded-full overflow-hidden mb-5">
            <div v-for="item in distribution" :key="item.label"
                :class="['h-full transition-all', MOOD_STYLE[item.label]?.bar ?? 'bg-gray-300']"
                :style="{ width: item.pct + '%' }" />
        </div>

        <!-- Legend grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-3">
            <div v-for="item in distribution" :key="item.label" class="flex items-center gap-3">
                <span :class="['w-3 h-3 rounded-full shrink-0', MOOD_STYLE[item.label]?.dot ?? 'bg-gray-400']" />
                <span class="text-sm text-text-secondary min-w-17.5">{{ item.label }}</span>
                <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                    <div :class="['h-full rounded-full', MOOD_STYLE[item.label]?.bar ?? 'bg-gray-400']"
                        :style="{ width: item.pct + '%' }" />
                </div>
                <span :class="['text-sm font-semibold min-w-15', MOOD_STYLE[item.label]?.text ?? 'text-gray-600']">
                    {{ item.pct }}% <span class="text-text-muted font-normal">({{ item.count }})</span>
                </span>
            </div>
        </div>
    </div>
</template>
