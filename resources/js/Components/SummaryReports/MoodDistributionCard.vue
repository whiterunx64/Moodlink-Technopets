<script setup lang="ts">
import type { MoodDistributionItem, SummaryPeriod } from '@/types';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import {
    ArcElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { computed } from 'vue';
import { Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    ArcElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    ChartDataLabels,
);

const props = defineProps<{
    distribution: MoodDistributionItem[];
    period: SummaryPeriod;
}>();

const emit = defineEmits<{
    'update:period': [period: SummaryPeriod];
}>();

const PERIODS: { key: SummaryPeriod; label: string }[] = [
    { key: 'this_week', label: 'This Week' },
    { key: 'this_month', label: 'This Month' },
    { key: 'all_time', label: 'All Time' },
];

const MOOD_COLOR: Record<string, string> = {
    Excited: '#22c55e',
    Content: '#3b82f6',
    Stressed: '#f97316',
    Drained: '#f87171',
};

const MOOD_TEXT: Record<string, string> = {
    Excited: 'text-green-600',
    Content: 'text-blue-600',
    Stressed: 'text-orange-500',
    Drained: 'text-red-400',
};

const MOOD_BG: Record<string, string> = {
    Excited: 'bg-green-50',
    Content: 'bg-blue-50',
    Stressed: 'bg-orange-50',
    Drained: 'bg-red-50',
};

const totalLogs = computed(() =>
    props.distribution.reduce((sum, d) => sum + d.count, 0),
);

/* -----------------------------------
   Doughnut Chart
----------------------------------- */

const doughnutData = computed(() => ({
    labels: props.distribution.map((d) => d.label),
    datasets: [
        {
            data: props.distribution.map((d) => d.count),
            backgroundColor: props.distribution.map(
                (d) => MOOD_COLOR[d.label] ?? '#d1d5db',
            ),
            borderWidth: 0,
            hoverOffset: 8,
        },
    ],
}));

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: {
            display: false,
        },
        datalabels: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label(context: any) {
                    const total = totalLogs.value;
                    const value = Number(context.raw);

                    const pct = total
                        ? ((value / total) * 100).toFixed(0)
                        : '0';

                    return `${context.label}: ${value} logs (${pct}%)`;
                },
            },
        },
    },
}));

/* -----------------------------------
   Multi Axis Line Chart
----------------------------------- */

const lineData = computed(() => ({
    labels: props.distribution.map((d) => d.label),

    datasets: [
        {
            label: 'Percentage (%)',
            data: props.distribution.map((d) => d.pct),

            borderColor: '#f43f5e',
            backgroundColor: '#f43f5e',
            pointBackgroundColor: '#f43f5e',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,

            tension: 0.35,

            yAxisID: 'y',
        },

        {
            label: 'Logs',
            data: props.distribution.map((d) => d.count),

            borderColor: '#3b82f6',
            backgroundColor: '#3b82f6',
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,

            tension: 0.35,

            yAxisID: 'y1',
        },
    ],
}));

const lineOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,

    interaction: {
        mode: 'index' as const,
        intersect: false,
    },

    plugins: {
        datalabels: {
            display: false,
        },

        legend: {
            position: 'top' as const,
            labels: {
                usePointStyle: true,
            },
        },

        tooltip: {
            callbacks: {
                label(context: any) {
                    const label = context.dataset.label;
                    const value = context.raw;

                    return `${label}: ${value}`;
                },
            },
        },
    },

    scales: {
        x: {
            grid: {
                color: '#e5e7eb',
            },
        },

        y: {
            type: 'linear' as const,
            position: 'left' as const,

            beginAtZero: true,
            max: 100,

            title: {
                display: true,
                text: 'Percentage (%)',
            },

            ticks: {
                stepSize: 20,
            },

            grid: {
                color: '#e5e7eb',
            },
        },

        y1: {
            type: 'linear' as const,
            position: 'right' as const,

            beginAtZero: true,

            title: {
                display: true,
                text: 'Logs',
            },

            grid: {
                drawOnChartArea: false,
            },
        },
    },
}));
</script>

<template>
    <div class="border-border-light overflow-hidden border bg-white shadow-sm">
        <div
            class="border-border-light flex flex-wrap items-center justify-between gap-3 border-b px-6 pt-5 pb-4"
        >
            <div>
                <h3 class="text-text-primary text-base font-semibold">
                    Mood Distribution
                </h3>
                <p class="text-text-muted mt-0.5 text-xs">
                    Emotional breakdown across all programs
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <button
                        v-for="p in PERIODS"
                        :key="p.key"
                        type="button"
                        :class="[
                            'cursor-pointer border px-3.5 py-1.5 text-xs font-medium transition-colors',
                            period === p.key
                                ? 'bg-sidebar border-sidebar text-white'
                                : 'text-text-secondary border-border-light bg-white hover:bg-[#fef]',
                        ]"
                        @click="emit('update:period', p.key)"
                    >
                        {{ p.label }}
                    </button>
                </div>

                <div class="bg-border-light h-5 w-px" />

                <button
                    type="button"
                    class="border-border-light text-text-secondary inline-flex cursor-pointer items-center gap-1.5 border bg-white px-3.5 py-1.5 text-xs font-medium transition-colors hover:bg-gray-50"
                >
                    <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                    Export PDF
                </button>
            </div>
        </div>

        <div
            v-if="totalLogs === 0"
            class="text-text-muted py-20 text-center text-sm"
        >
            No mood data available for this period.
        </div>

        <div
            v-else
            class="divide-border-light grid grid-cols-1 divide-y lg:grid-cols-5 lg:divide-x lg:divide-y-0"
        >
            <!-- Doughnut -->

            <div
                class="flex flex-col items-center justify-center gap-2 px-6 py-6 lg:col-span-2"
            >
                <div class="relative h-55 w-full max-w-55">
                    <Doughnut :data="doughnutData" :options="doughnutOptions" />

                    <div
                        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="text-text-primary text-3xl font-extrabold">
                            {{ totalLogs }}
                        </span>

                        <span class="text-text-muted text-xs font-medium">
                            Total Logs
                        </span>
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap justify-center gap-2">
                    <span
                        v-for="item in distribution"
                        :key="item.label"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold',
                            MOOD_BG[item.label],
                        ]"
                    >
                        <span
                            class="h-2 w-2"
                            :style="{ backgroundColor: MOOD_COLOR[item.label] }"
                        />

                        <span :class="MOOD_TEXT[item.label]">
                            {{ item.label }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Multi Axis Line Chart -->

            <div
                class="flex flex-col justify-center gap-5 px-6 py-6 lg:col-span-3"
            >
                <div class="h-80">
                    <Line :data="lineData" :options="lineOptions" />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="item in distribution"
                        :key="item.label"
                        :class="['p-3 text-center', MOOD_BG[item.label]]"
                    >
                        <p
                            :class="[
                                'text-xl font-extrabold',
                                MOOD_TEXT[item.label],
                            ]"
                        >
                            {{ item.pct }}%
                        </p>

                        <p class="text-text-muted mt-0.5 text-xs">
                            {{ item.label }}
                        </p>

                        <p
                            :class="[
                                'mt-0.5 text-xs font-semibold',
                                MOOD_TEXT[item.label],
                            ]"
                        >
                            {{ item.count }} logs
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
