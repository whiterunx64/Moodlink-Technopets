<script setup lang="ts">
import type { MoodTrendPoint } from '@/types';
import type { ChartData, ChartOptions } from 'chart.js';
import { ArcElement, Chart as ChartJS, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

// Only register what we use — faster init, no Legend bloat
ChartJS.register(ArcElement, Tooltip);

const props = defineProps<{
    data: MoodTrendPoint[];
    trend: 'Declining' | 'Stable' | 'Improving';
    trendDays: number;
}>();

const emit = defineEmits<{
    'update:trendDays': [days: number];
}>();

// Semantic, perceptually distinct colours — not a rainbow
const MOOD_PALETTE: Record<number, { fill: string; label: string }> = {
    4: { fill: '#F2C94C', label: 'Excited' },
    3: { fill: '#6FCF88', label: 'Content' },
    2: { fill: '#EB5757', label: 'Stressed' },
    1: { fill: '#8DA9C4', label: 'Drained' },
};

const TREND_META = {
    Declining: { symbol: '↓', class: 'trend--down' },
    Stable: { symbol: '→', class: 'trend--neutral' },
    Improving: { symbol: '↑', class: 'trend--up' },
} as const;

// Highest-scored mood for the centre label
const dominantMood = computed(() => {
    if (!props.data.length) return null;
    const top = [...props.data]
        .filter(
            (d): d is MoodTrendPoint & { score: number } => d.score !== null,
        )
        .sort((a, b) => b.score - a.score)[0];
    if (!top) return 'Unknown';
    return MOOD_PALETTE[top.score]?.label ?? 'Unknown';
});

// Fix 2: explicitly type as ChartData<'doughnut'> and replace null scores with 0
const chartData = computed<ChartData<'doughnut', number[], unknown>>(() => ({
    labels: props.data.map(
        (d) =>
            (d.score !== null ? MOOD_PALETTE[d.score]?.label : undefined) ??
            'Unknown',
    ),
    datasets: [
        {
            data: props.data.map((d) => d.score ?? 0), // null → 0
            backgroundColor: props.data.map(
                (d) =>
                    (d.score !== null
                        ? MOOD_PALETTE[d.score]?.fill
                        : undefined) ?? '#898781',
            ),
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 8,
        },
    ],
}));

// Fix 3: explicitly type as ChartOptions<'doughnut'> and use a valid weight literal
const chartOptions = computed<ChartOptions<'doughnut'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0f0f0e',
            bodyFont: {
                size: 13,
                family: "'IBM Plex Mono', monospace",
                weight: 'bold' as const,
            },
            padding: 10,
            cornerRadius: 2,
            callbacks: {
                label(context) {
                    return `  ${context.label}: ${context.raw}`;
                },
            },
        },
    },
}));
</script>

<template>
    <!--
        Design intent: same editorial system as MoodTrendsCard.
        Ink-dark header strip, ruled dividers, monospace numerals,
        no decorative shadows or gradients.
    -->
    <article class="trend-widget" aria-label="Mood trend overview">
        <!-- Header -->
        <header class="widget-header">
            <div>
                <span class="widget-eyebrow">Last {{ trendDays }} days</span>
                <h3 class="widget-title">Mood Trend</h3>
            </div>

            <div class="day-toggle" role="group" aria-label="Time range">
                <button
                    v-for="d in [7, 30]"
                    :key="d"
                    type="button"
                    :aria-pressed="trendDays === d"
                    :class="[
                        'day-btn',
                        trendDays === d ? 'day-btn--active' : '',
                    ]"
                    @click="emit('update:trendDays', d)"
                >
                    {{ d }}d
                </button>
            </div>
        </header>

        <!-- Trend indicator -->
        <div class="trend-bar">
            <span
                :class="['trend-badge', TREND_META[trend].class]"
                aria-label="`Trend: ${trend}`"
            >
                {{ TREND_META[trend].symbol }} {{ trend }}
            </span>
        </div>

        <!-- Chart area -->
        <div class="chart-area">
            <div
                class="chart-inner"
                role="img"
                :aria-label="`Mood distribution doughnut chart. Dominant mood: ${dominantMood}`"
            >
                <Doughnut :data="chartData" :options="chartOptions" />
            </div>

            <!-- Centre label -->
            <div class="chart-label" aria-hidden="true">
                <span class="chart-label-value">{{ dominantMood }}</span>
                <span class="chart-label-sub">leading</span>
            </div>
        </div>

        <!-- Legend — inline ruled table -->
        <footer class="widget-legend" role="list" aria-label="Mood key">
            <div
                v-for="[score, meta] in Object.entries(MOOD_PALETTE).reverse()"
                :key="score"
                class="legend-item"
                role="listitem"
            >
                <span
                    class="legend-dot"
                    :style="{ background: meta.fill }"
                    aria-hidden="true"
                />
                <span class="legend-name">{{ meta.label }}</span>
                <span class="legend-score">{{ score }}</span>
            </div>
        </footer>
    </article>
</template>

<style scoped>
/* ─── Tokens ─────────────────────────────────────────────────────────── */
.trend-widget {
    --ink: #0f0f0e;
    --ink-mid: #52514e;
    --ink-faint: #898781;
    --rule: #e1e0d9;
    --surface: #ffffff;
    --up: #16783a;
    --neutral: #52514e;
    --down: #c13434;

    display: flex;
    flex-direction: column;
    background: var(--surface);
    border: 1.5px solid var(--ink);
    font-family: 'Inter', system-ui, sans-serif;
}

/* ─── Header ─────────────────────────────────────────────────────────── */
.widget-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 1.25rem 1.375rem 1rem;
    background: var(--ink);
    border-bottom: 1.5px solid var(--ink);
}

.widget-eyebrow {
    display: block;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #898781;
    margin-bottom: 4px;
}

.widget-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #ffffff;
    line-height: 1;
}

/* Day toggle — two-button inline switcher on dark bg */
.day-toggle {
    display: flex;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.day-btn {
    padding: 5px 14px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    background: transparent;
    color: rgba(255, 255, 255, 0.45);
    border: none;
    cursor: pointer;
    transition:
        background 0.1s,
        color 0.1s;
}

.day-btn + .day-btn {
    border-left: 1px solid rgba(255, 255, 255, 0.2);
}

.day-btn:hover:not(.day-btn--active) {
    color: rgba(255, 255, 255, 0.75);
}

.day-btn--active {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
}

/* ─── Trend bar ───────────────────────────────────────────────────────── */
.trend-bar {
    padding: 0.625rem 1.375rem;
    border-bottom: 1px solid var(--rule);
}

.trend-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.trend--up {
    color: var(--up);
}

.trend--neutral {
    color: var(--neutral);
}

.trend--down {
    color: var(--down);
}

/* ─── Chart area ─────────────────────────────────────────────────────── */
.chart-area {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
    border-bottom: 1px solid var(--rule);
}

.chart-inner {
    width: 240px;
    height: 240px;
    flex-shrink: 0;
}

.chart-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
    line-height: 1;
}

.chart-label-value {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ink);
    white-space: nowrap;
}

.chart-label-sub {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--ink-faint);
    margin-top: 5px;
}

/* ─── Legend ─────────────────────────────────────────────────────────── */
.widget-legend {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-top: 1.5px solid var(--ink);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--rule);
    font-size: 12px;
}

/* Remove bottom border from last two items */
.legend-item:nth-last-child(-n + 2) {
    border-bottom: none;
}

/* Right column items get a left border */
.legend-item:nth-child(even) {
    border-left: 1px solid var(--rule);
}

.legend-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

.legend-name {
    flex: 1;
    color: var(--ink-mid);
    font-weight: 500;
}

.legend-score {
    font-family: 'IBM Plex Mono', 'Courier New', monospace;
    font-size: 11px;
    font-weight: 700;
    color: var(--ink-faint);
    font-variant-numeric: tabular-nums;
}
</style>
