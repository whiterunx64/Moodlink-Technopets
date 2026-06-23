<script setup lang="ts">
import { computed } from 'vue';
import { use } from 'echarts/core';
import VChart from 'vue-echarts';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart, BarChart } from 'echarts/charts';
import {
    GridComponent,
    TooltipComponent,
    LegendComponent,
} from 'echarts/components';
import type { EChartsOption } from 'echarts';
import type { MoodDistributionItem, SummaryPeriod } from '@/types';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

use([CanvasRenderer, PieChart, BarChart, GridComponent, TooltipComponent, LegendComponent]);

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

const donutOption = computed<EChartsOption>(() => ({
    tooltip: {
        trigger: 'item',
        formatter: (p: any) => `<b>${p.name}</b><br/>${p.value} logs (${p.percent}%)`,
    },
    series: [
        {
            type: 'pie',
            radius: ['52%', '78%'],
            avoidLabelOverlap: false,
            label: { show: false },
            emphasis: {
                scale: true,
                scaleSize: 6,
                itemStyle: { shadowBlur: 12, shadowColor: 'rgba(0,0,0,0.15)' },
            },
            data: props.distribution.map((d) => ({
                name: d.label,
                value: d.count,
                itemStyle: { color: MOOD_COLOR[d.label] ?? '#d1d5db' },
            })),
        },
    ],
}));

const barOption = computed<EChartsOption>(() => ({
    grid: { left: 74, right: 72, top: 8, bottom: 8, containLabel: false },
    tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'none' },
        formatter: (params: any) => {
            const p = Array.isArray(params) ? params[0] : params;
            const item = props.distribution.find((d) => d.label === p.name);
            return `<b>${p.name}</b>: ${item?.count ?? 0} logs (${item?.pct ?? 0}%)`;
        },
    },
    xAxis: { type: 'value', max: 100, show: false },
    yAxis: {
        type: 'category',
        data: [...props.distribution].reverse().map((d) => d.label),
        axisLabel: { color: '#6b7280', fontSize: 12, fontWeight: 500 },
        axisLine: { show: false },
        axisTick: { show: false },
    },
    series: [
        {
            type: 'bar',
            data: [...props.distribution].reverse().map((d) => ({
                value: d.pct,
                itemStyle: {
                    color: MOOD_COLOR[d.label] ?? '#d1d5db',
                    borderRadius: [0, 4, 4, 0],
                },
            })),
            barMaxWidth: 20,
            label: {
                show: true,
                position: 'right',
                formatter: (p: any) => {
                    const item = props.distribution.find(
                        (d) => d.label === [...props.distribution].reverse()[p.dataIndex]?.label,
                    );
                    return `{pct|${item?.pct ?? 0}%}  {cnt|(${item?.count ?? 0})}`;
                },
                rich: {
                    pct: { fontWeight: 700, fontSize: 12, color: '#374151' },
                    cnt: { fontSize: 11, color: '#9ca3af' },
                },
            },
            backgroundStyle: { color: '#f3f4f6', borderRadius: [0, 4, 4, 0] },
            showBackground: true,
        },
    ],
}));
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm overflow-hidden">
        <!-- Header with embedded period filter -->
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-5 pb-4 border-b border-border-light">
            <div>
                <h3 class="text-base font-semibold text-text-primary">Mood Distribution</h3>
                <p class="text-xs text-text-muted mt-0.5">Emotional breakdown across all sections</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <!-- Period pills -->
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

                <!-- Export PDF -->
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

            <!-- Donut chart -->
            <div class="lg:col-span-2 flex flex-col items-center justify-center px-6 py-6 gap-2">
                <div class="relative w-full max-w-50">
                    <VChart :option="donutOption" style="width: 100%; height: 200px;" autoresize />
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-3xl font-extrabold text-text-primary">{{ totalLogs }}</span>
                        <span class="text-xs text-text-muted font-medium">Total Logs</span>
                    </div>
                </div>

                <!-- Mood pills -->
                <div class="flex flex-wrap justify-center gap-2 mt-1">
                    <span v-for="item in distribution" :key="item.label"
                        :class="['flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold', MOOD_BG[item.label]]">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: MOOD_COLOR[item.label] }" />
                        <span :class="MOOD_TEXT[item.label]">{{ item.label }}</span>
                    </span>
                </div>
            </div>

            <!-- Bar chart + stat rows -->
            <div class="lg:col-span-3 flex flex-col justify-center px-6 py-6 gap-5">
                <VChart :option="barOption" style="width: 100%; height: 140px;" autoresize />

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="item in distribution" :key="item.label"
                        :class="['rounded-xl p-3 text-center', MOOD_BG[item.label]]">
                        <p :class="['text-xl font-extrabold', MOOD_TEXT[item.label]]">{{ item.pct }}%</p>
                        <p class="text-xs text-text-muted mt-0.5">{{ item.label }}</p>
                        <p :class="['text-xs font-semibold mt-0.5', MOOD_TEXT[item.label]]">{{ item.count }} logs</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
