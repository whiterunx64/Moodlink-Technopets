<script setup lang="ts">
import type { MoodTrendPoint } from '@/types';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    ChartDataLabels,
);

const props = defineProps<{
    data: MoodTrendPoint[];
    trendDays: number;
}>();

const emit = defineEmits<{
    'update:trendDays': [days: number];
    // A specific day + mood was clicked; parent shows those posts.
    'select-day': [payload: { point: MoodTrendPoint; mood: string }];
}>();

// The four moods, their emoji, and line colours. Order fixed (never cycled).
const MOODS = [
    { key: 'Excited', emoji: '⚡', color: '#34D399' },
    { key: 'Content', emoji: '🍀', color: '#60A5FA' },
    { key: 'Stressed', emoji: '🌧️', color: '#FBBF24' },
    { key: 'Drained', emoji: '😤', color: '#F87171' },
] as const;


// Per-day count of each mood, derived from that day's posts.
function moodCount(point: MoodTrendPoint, mood: string): number {
    return point.posts.reduce((n, p) => (p.mood === mood ? n + 1 : n), 0);
}

// A translucent version of a mood colour for the filled area under its line.
function fillColor(hex: string): string {
    const n = parseInt(hex.slice(1), 16);
    const r = (n >> 16) & 255;
    const g = (n >> 8) & 255;
    const b = n & 255;
    return `rgba(${r}, ${g}, ${b}, 0.25)`;
}

const hasAnyData = computed(() =>
    props.data.some((d) => d.posts.length > 0),
);

// X = date, Y = post count. One filled line per mood; each point carries the
// mood emoji. Filled-area style matches the Chart.js line sample.
const chartData = computed<ChartData<'line', (number | null)[], string>>(
    () => ({
        labels: props.data.map((d) => d.label),
        datasets: MOODS.map((m) => ({
            label: m.key,
            // 0 (not null) so every mood draws a continuous, visible line.
            data: props.data.map((d) => moodCount(d, m.key)),
            borderColor: m.color,
            backgroundColor: fillColor(m.color),
            borderWidth: 3,
            fill: 'origin',
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointHitRadius: 14,
            pointBackgroundColor: m.color,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            datalabels: {
                // Emoji only where this mood actually has posts that day.
                display: (ctx) => (ctx.dataset.data[ctx.dataIndex] as number) > 0,
                formatter: () => m.emoji,
                font: { size: 16 },
                anchor: 'end',
                align: 'top',
                offset: 2,
            },
        })),
    }),
);

const chartOptions = computed<ChartOptions<'line'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: { top: 8, right: 16, bottom: 4, left: 4 } },
    onClick(_event, elements) {
        const first = elements[0];
        if (!first) return;
        const point = props.data[first.index];
        const mood = MOODS[first.datasetIndex]?.key;
        if (point && mood && moodCount(point, mood) > 0) {
            emit('select-day', { point, mood });
        }
    },
    onHover(event, elements) {
        const target = event.native?.target as HTMLElement | undefined;
        if (target) {
            target.style.cursor = elements.length ? 'pointer' : 'default';
        }
    },
    scales: {
        x: {
            title: { display: true, text: 'Date', color: '#898781' },
            ticks: {
                color: '#898781',
                font: { size: 11 },
                maxRotation: 0,
                autoSkipPadding: 12,
            },
            grid: { display: false },
            border: { color: '#e1e0d9' },
        },
        y: {
            beginAtZero: true,
            suggestedMax: 20, // scale so the axis reads 0..20 by default
            title: { display: true, text: 'Number of MoodSpace posts', color: '#898781' },
            ticks: {
                precision: 0,
                color: '#898781',
                font: { size: 11 },
            },
            grid: { color: '#f0efe9' },
            border: { color: '#e1e0d9' },
        },
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f0f0e',
            padding: 10,
            cornerRadius: 2,
            titleFont: { size: 12, weight: 'bold' },
            bodyFont: { size: 12 },
            callbacks: {
                title: (items) => {
                    const point = props.data[items[0].dataIndex];
                    return point?.date ?? '';
                },
                label: (item) => {
                    const mood = MOODS[item.datasetIndex];
                    const count = item.parsed.y;
                    return `${mood.emoji} ${mood.key}: ${count} post${count === 1 ? '' : 's'} (click to view)`;
                },
            },
        },
    },
    interaction: { mode: 'nearest', intersect: true },
}));
</script>

<template>
    <div class="bg-white border border-border-light shadow-sm p-6" aria-label="Mood trend over time">
        <!-- Header row: title + trend badge on the left, day toggle on the right -->
        <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-text-primary">MoodSpace Posts Data</h3>
            </div>

            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5" role="group" aria-label="Time range">
                <button
                    v-for="d in [7, 30]"
                    :key="d"
                    type="button"
                    :aria-pressed="trendDays === d"
                    :class="[
                        'px-3 py-1 text-xs font-semibold rounded-md transition-colors',
                        trendDays === d
                            ? 'bg-white text-text-primary shadow-sm'
                            : 'text-text-muted hover:text-text-secondary',
                    ]"
                    @click="emit('update:trendDays', d)"
                >
                    {{ d }}D
                </button>
            </div>
        </div>

        <!-- Mood key -->
        <div class="flex items-center gap-4 mb-4 flex-wrap">
            <div v-for="m in MOODS" :key="m.key" class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full" :style="{ background: m.color }" />
                <span class="text-xs text-text-muted">{{ m.emoji }} {{ m.key }}</span>
            </div>
        </div>

        <!-- Chart — always rendered; the empty range still shows the axes/dates -->
        <div class="relative h-128">
            <Line :data="chartData" :options="chartOptions" />
            <p
                v-if="!hasAnyData"
                class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-text-muted"
            >
                No MoodSpace posts in this period.
            </p>
        </div>

        <p class="text-xs text-text-muted mt-4">Click a mood on a date to read those MoodSpace posts.</p>
    </div>
</template>
