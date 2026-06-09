<script setup lang="ts">
import { computed } from 'vue';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart } from 'echarts/charts';
import {
    GridComponent,
    TooltipComponent,
    MarkLineComponent,
} from 'echarts/components';
import type { EChartsOption } from 'echarts';
import type { MoodTrendPoint } from '@/types';

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent, MarkLineComponent]);

const props = defineProps<{
    data: MoodTrendPoint[];
    trend: 'Declining' | 'Stable' | 'Improving';
    trendDays: number;
}>();

const emit = defineEmits<{
    'update:trendDays': [days: number];
}>();

const TREND_STYLE: Record<string, string> = {
    Declining: 'text-red-500',
    Stable:    'text-text-muted',
    Improving: 'text-green-600',
};

const SCORE_TO_LABEL: Record<number, string> = {
    4: 'Excited',
    3: 'Content',
    2: 'Stressed',
    1: 'Drained',
};

const option = computed<EChartsOption>(() => ({
    grid: {
        left: 70,
        right: 16,
        top: 12,
        bottom: props.trendDays === 30 ? 48 : 32,
    },
    tooltip: {
        trigger: 'axis',
        formatter: (params: any) => {
            const p = Array.isArray(params) ? params[0] : params;
            const score = p.data as number | null;
            const label = score !== null ? (SCORE_TO_LABEL[score] ?? score) : '—';
            return `<b>${p.axisValue}</b>: ${label}`;
        },
    },
    xAxis: {
        type: 'category',
        data: props.data.map((d) => d.label),
        axisLabel: {
            color: '#9ca3af',
            fontSize: 10,
            rotate: props.trendDays === 30 ? 45 : 0,
            interval: props.trendDays === 30 ? 1 : 0,
        },
        axisLine: { show: false },
        axisTick: { show: false },
    },
    yAxis: {
        type: 'value',
        min: 1,
        max: 4,
        interval: 1,
        axisLabel: {
            color: '#9ca3af',
            fontSize: 10,
            formatter: (v: number) => SCORE_TO_LABEL[v] ?? '',
        },
        splitLine: { lineStyle: { color: '#e5e7eb' } },
        axisLine: { show: false },
        axisTick: { show: false },
    },
    series: [
        {
            type: 'line',
            data: props.data.map((d) => d.score),
            smooth: false,
            symbol: 'circle',
            symbolSize: 8,
            itemStyle: { color: '#ffffff', borderColor: '#4f46e5', borderWidth: 2 },
            lineStyle: { color: '#4f46e5', width: 2 },
            areaStyle: { color: 'rgba(99,102,241,0.08)' },
            connectNulls: true,
        },
    ],
}));
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-semibold text-text-primary">Mood Trend</h3>
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button
                    v-for="d in [7, 30]"
                    :key="d"
                    type="button"
                    :class="[
                        'px-3 py-1 rounded-md text-xs font-medium transition-all',
                        trendDays === d
                            ? 'bg-sidebar text-white shadow-sm'
                            : 'text-text-muted hover:text-text-secondary',
                    ]"
                    @click="emit('update:trendDays', d)"
                >
                    {{ d }} Days
                </button>
            </div>
        </div>

        <p :class="['text-xs font-medium mb-4', TREND_STYLE[trend]]">
            Mood is <span class="font-semibold">{{ trend }}</span>
        </p>

        <VChart :option="option" :style="{ width: '100%', height: '220px' }" autoresize />
    </div>
</template>
