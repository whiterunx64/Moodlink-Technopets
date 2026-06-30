<script setup lang="ts">
import type { MoodTrendsData } from '@/types';
import { ChartPieIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ArcElement, Chart as ChartJS, Tooltip } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip);

const props = defineProps<{
    data: MoodTrendsData;
    statPeriod: 'today' | 'week' | 'month';
}>();

const PERIOD_MAP: Record<string, string> = {
    today: 'Today',
    week: 'Weekly',
    month: 'Monthly',
};

const MOOD_FILL: Record<string, string> = {
    Excited: '#F2C94C',
    Content: '#6FCF88',
    Stressed: '#EB5757',
    Drained: '#8DA9C4',
};

function selectProgram(event: Event) {
    router.get(
        route('dashboard'),
        {
            trendPeriod: PERIOD_MAP[props.statPeriod] ?? 'Today',
            trendProgram: (event.target as HTMLSelectElement).value,
        },
        { preserveState: true, preserveScroll: true, only: ['mood_trends'] },
    );
}

const dominantMood = computed(() => {
    if (!props.data.total) return null;
    return (
        [...props.data.distribution].sort((a, b) => b.pct - a.pct)[0]?.label ??
        null
    );
});

const chartData = computed(() => ({
    labels: props.data.distribution.map((d) => d.label),
    datasets: [
        {
            data: props.data.distribution.map((d) => d.pct),
            backgroundColor: props.data.distribution.map(
                (d) => MOOD_FILL[d.label] ?? '#898781',
            ),
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 8,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f0f0e',
            bodyFont: { size: 13, weight: 500 },
            padding: 10,
            cornerRadius: 6,
            callbacks: {
                label(context: any) {
                    return `  ${context.label}: ${context.raw}%`;
                },
            },
        },
        datalabels: {
            color: '#ffffff',
            font: { size: 12, weight: 'bold' as const },
            formatter: (value: number) => (value >= 8 ? `${value}%` : ''),
        },
    },
};
</script>

<template>
    <article
        class="border-border-light flex flex-col border bg-white shadow-sm"
        aria-label="Mood trend overview"
    >
        <!-- Header -->
        <div
            class="border-border-light flex shrink-0 items-center justify-between border-b px-5 pt-5 pb-4"
        >
            <div>
                <h3 class="text-text-primary text-base font-semibold">
                    Mood Trend
                </h3>
                <p class="text-text-muted mt-0.5 text-xs">
                    {{ data.total }} mood logs
                </p>
            </div>
        </div>

        <!-- Program filter -->
        <div class="flex items-center justify-end gap-2 px-5 pt-4">
            <select
                class="border-border-light bg-post-card-bg text-text-primary focus:ring-sidebar/30 ml-2 w-auto max-w-37.5 shrink-0 cursor-pointer border px-3 py-2 text-xs font-medium focus:ring-2 focus:outline-none"
                :value="data.program"
                aria-label="Program"
                @change="selectProgram"
            >
                <option
                    v-for="program in data.programs"
                    :key="program"
                    :value="program"
                >
                    {{ program === 'All' ? 'All programs' : program }}
                </option>
            </select>
        </div>

        <!-- Chart area -->
        <div
            class="relative flex min-h-45 flex-1 items-center justify-center px-5 py-6"
        >
            <template v-if="data.total > 0">
                <div
                    class="h-50 w-50 shrink-0"
                    role="img"
                    :aria-label="`Mood distribution doughnut chart. Dominant mood: ${dominantMood}`"
                >
                    <Doughnut
                        :data="chartData"
                        :options="chartOptions"
                        :plugins="[ChartDataLabels]"
                    />
                </div>
                <div
                    class="pointer-events-none absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center"
                    aria-hidden="true"
                >
                    <span
                        class="text-text-primary text-base font-semibold whitespace-nowrap"
                        >{{ dominantMood }}</span
                    >
                    <span
                        class="text-text-muted mt-0.5 text-[10px] font-medium tracking-wide uppercase"
                        >leading</span
                    >
                </div>
            </template>
            <div
                v-else
                class="text-text-muted flex flex-col items-center justify-center gap-3 text-sm"
            >
                <ChartPieIcon
                    class="h-16 w-16 text-blue-500/60"
                    aria-hidden="true"
                />
                No mood logs for this period.
            </div>
        </div>

        <!-- Legend -->
        <footer
            class="border-border-light grid grid-cols-2 gap-2 border-t p-4"
            role="list"
            aria-label="Mood key"
        >
            <div
                v-for="bar in data.distribution"
                :key="bar.label"
                class="bg-post-card-bg hover:bg-post-card-bg-hover flex items-center gap-2 px-3 py-2 transition-colors"
                role="listitem"
            >
                <span
                    class="h-2 w-2 shrink-0"
                    :style="{ background: MOOD_FILL[bar.label] ?? '#898781' }"
                    aria-hidden="true"
                />
                <span
                    class="text-text-primary flex-1 truncate text-xs font-medium"
                    >{{ bar.label }}</span
                >
                <span class="text-text-muted text-xs font-semibold"
                    >{{ bar.pct }}%</span
                >
            </div>
        </footer>
    </article>
</template>
