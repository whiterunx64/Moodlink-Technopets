<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import type { MoodDistributionItem, SummaryPeriod } from '@/types';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

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
    <div class="bg-white rounded-2xl border border-border-light shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-5 pb-4 border-b border-border-light">
            <div>
                <h3 class="text-base font-semibold text-text-primary">
                    Mood Distribution
                </h3>
                <p class="text-xs text-text-muted mt-0.5">
                    Emotional breakdown across all programs
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <button v-for="p in PERIODS" :key="p.key" type="button" :class="[
                        'px-3.5 py-1.5 rounded-full text-xs font-medium border transition-colors',
                        period === p.key
                            ? 'bg-sidebar text-white border-sidebar'
                            : 'bg-white text-text-secondary border-border-light hover:bg-gray-50',
                    ]" @click="emit('update:period', p.key)">
                        {{ p.label }}
                    </button>
                </div>

                <div class="w-px h-5 bg-border-light" />

                <button type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-medium border border-border-light bg-white text-text-secondary hover:bg-gray-50 transition-colors">
                    <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                    Export PDF
                </button>
            </div>
        </div>

        <div v-if="totalLogs === 0" class="py-20 text-center text-sm text-text-muted">
            No mood data available for this period.
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-border-light">
            <!-- Doughnut -->

            <div class="lg:col-span-2 flex flex-col items-center justify-center px-6 py-6 gap-2">
                <div class="relative w-full max-w-55 h-55">
                    <Doughnut :data="doughnutData" :options="doughnutOptions" />

                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-3xl font-extrabold text-text-primary">
                            {{ totalLogs }}
                        </span>

                        <span class="text-xs text-text-muted font-medium">
                            Total Logs
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap justify-center gap-2 mt-2">
                    <span v-for="item in distribution" :key="item.label" :class="[
                        'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                        MOOD_BG[item.label],
                    ]">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: MOOD_COLOR[item.label] }" />

                        <span :class="MOOD_TEXT[item.label]">
                            {{ item.label }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Multi Axis Line Chart -->

            <div class="lg:col-span-3 flex flex-col justify-center px-6 py-6 gap-5">
                <div class="h-80">
                    <Line :data="lineData" :options="lineOptions" />
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="item in distribution" :key="item.label" :class="[
                        'rounded-xl p-3 text-center',
                        MOOD_BG[item.label],
                    ]">
                        <p :class="[
                            'text-xl font-extrabold',
                            MOOD_TEXT[item.label],
                        ]">
                            {{ item.pct }}%
                        </p>

                        <p class="text-xs text-text-muted mt-0.5">
                            {{ item.label }}
                        </p>

                        <p :class="[
                            'text-xs font-semibold mt-0.5',
                            MOOD_TEXT[item.label],
                        ]">
                            {{ item.count }} logs
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>