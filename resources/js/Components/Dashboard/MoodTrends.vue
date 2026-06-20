<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DistributionItem {
    label: string;
    pct: number;
    color: string;
}

export interface MoodTrendsData {
    period: 'Today' | 'Weekly' | 'Monthly';
    section: string;
    sections: string[];
    total: number;
    distribution: DistributionItem[];
}

const props = defineProps<{
    /** Mood-distribution aggregate from AdminDashboardService, server-rendered. */
    data: MoodTrendsData;
}>();

const tabs = ['Today', 'Weekly', 'Monthly'] as const;

const loading = ref(false);
const pendingPeriod = ref<string | null>(null);
const pendingSection = ref<string | null>(null);

const activePeriod = computed(() => pendingPeriod.value ?? props.data.period);
const activeSection = computed(
    () => pendingSection.value ?? props.data.section,
);

let inflightController: AbortController | null = null;

function applyFilter(period: string, section: string) {
    if (period === activePeriod.value && section === activeSection.value)
        return;

    // Cancel any in-flight request before firing a new one
    inflightController?.abort();
    inflightController = new AbortController();

    pendingPeriod.value = period;
    pendingSection.value = section;
    loading.value = true;

    router.reload({
        only: ['moodTrends'],
        data: { trendPeriod: period, trendSection: section },
        preserveUrl: true,
        onFinish: () => {
            loading.value = false;
            pendingPeriod.value = null;
            pendingSection.value = null;
            inflightController = null;
        },
    });
}
</script>

<template>
    <div
        class="border-border-light flex h-full min-h-120 flex-col overflow-hidden rounded-2xl border bg-white shadow-sm">
        <!-- Header -->
        <div class="border-border-light flex items-center justify-between border-b px-5 pt-5 pb-4">
            <div>
                <h3 class="text-text-primary text-base font-semibold">
                    Mood Trends
                </h3>
                <p class="text-text-muted mt-0.5 text-xs">
                    Emotional distribution overview
                </p>
            </div>
            <svg v-if="loading" class="text-text-muted h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
        </div>

        <div class="flex flex-1 flex-col gap-4 p-5">
            <!-- Tabs -->
            <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
                <button v-for="tab in tabs" :key="tab" type="button" :disabled="loading" :class="[
                    'flex-1 rounded-md py-1.5 text-xs font-medium transition-all duration-150 disabled:cursor-not-allowed',
                    activePeriod === tab
                        ? 'text-text-primary bg-white shadow-sm'
                        : 'text-text-muted hover:text-text-secondary',
                ]" @click="applyFilter(tab, activeSection)">
                    {{ tab }}
                </button>
            </div>

            <!-- Section Pills -->
            <div class="flex flex-wrap gap-1.5">
                <button v-for="s in props.data.sections" :key="s" type="button" :disabled="loading" :class="[
                    'rounded-full px-3 py-1 text-xs font-medium transition-all duration-150 disabled:cursor-not-allowed',
                    activeSection === s
                        ? 'bg-sidebar text-white'
                        : 'bg-pill-inactive-bg text-pill-inactive-text hover:bg-pill-inactive-bg-hover',
                ]" @click="applyFilter(activePeriod, s)">
                    {{ s }}
                </button>
            </div>

            <!-- Mood Distribution Bars -->
            <div v-if="props.data.total > 0" :class="[
                'flex flex-1 flex-col justify-center gap-3 transition-opacity duration-150',
                loading ? 'opacity-50' : '',
            ]">
                <div v-for="item in props.data.distribution" :key="item.label" class="space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-text-secondary font-medium">{{
                            item.label
                            }}</span>
                        <span class="text-text-muted">{{ item.pct }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div :class="[
                            'h-full rounded-full transition-all duration-500',
                            item.color,
                        ]" :style="{ width: item.pct + '%' }" />
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-1 flex-col items-center justify-center gap-1 py-6 text-center">
                <i class="fas fa-chart-simple text-text-muted/50 text-2xl" />
                <p class="text-text-muted text-xs">
                    No mood logs for this period
                </p>
            </div>

            <!-- Legend -->
            <div class="border-border-light grid grid-cols-2 gap-2 border-t pt-2">
                <div v-for="item in props.data.distribution" :key="item.label" class="flex items-center gap-1.5">
                    <div :class="[
                        'h-2.5 w-2.5 shrink-0 rounded-full',
                        item.color,
                    ]" />
                    <span class="text-text-muted text-xs">{{
                        item.label
                        }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
