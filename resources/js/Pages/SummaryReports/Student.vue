<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MoodTrendChart from '@/Components/SummaryReports/MoodTrendChart.vue';
import PeriodFilter from '@/Components/SummaryReports/PeriodFilter.vue';
import type { StudentMoodReport, SummaryPeriod } from '@/types';

const props = defineProps<{
    studentReport: StudentMoodReport;
    filters: { period: SummaryPeriod; trendDays: number };
}>();

// Bar and dot colors for the mood summary chart.
const MOOD_STYLE: Record<string, { bar: string; dot: string }> = {
    Excited: { bar: 'bg-amber-400', dot: 'bg-amber-400' },
    Content: { bar: 'bg-green-500', dot: 'bg-green-500' },
    Stressed: { bar: 'bg-red-400', dot: 'bg-red-400' },
    Drained: { bar: 'bg-slate-400', dot: 'bg-slate-400' },
};

// Tag colors for the recent mood log list.
const LOG_TAG: Record<string, string> = {
    Excited: 'bg-green-100 text-green-700',
    Content: 'bg-blue-100 text-blue-700',
    Stressed: 'bg-orange-100 text-orange-700',
    Drained: 'bg-red-100 text-red-700',
};

const MOOD_ORDER = ['Excited', 'Content', 'Stressed', 'Drained'] as const;

const moodBars = computed(() => {
    const { mood_summary: moodSummary } = props.studentReport;
    const values: Record<string, number> = {
        Excited: moodSummary.excited,
        Content: moodSummary.content,
        Stressed: moodSummary.stressed,
        Drained: moodSummary.drained,
    };
    const max = Math.max(...Object.values(values), 1);
    return MOOD_ORDER.map((mood) => ({
        mood,
        value: values[mood],
        pct: Math.round((values[mood] / max) * 100),
    }));
});

function onPeriodChange(p: SummaryPeriod) {
    router.get(
        route('reports.students.show', props.studentReport.id),
        { period: p, trendDays: props.filters.trendDays },
        { preserveState: true, replace: true },
    );
}

function onTrendDaysChange(days: number) {
    router.get(
        route('reports.students.show', props.studentReport.id),
        { period: props.filters.period, trendDays: days },
        { preserveState: true, replace: true, only: ['studentReport', 'filters'] },
    );
}

function goBack() {
    router.get(
        route('reports.programs.show', props.studentReport.program),
        { period: props.filters.period },
    );
}
</script>

<template>

    <Head title="Student Mood Report" />

    <AdminLayout title="Student Mood Report">
        <div class="space-y-5">

            <PeriodFilter :model-value="filters.period" @update:model-value="onPeriodChange" />

            <div class="flex items-center gap-3">
                <button type="button"
                    class="w-8 h-8 rounded-full border border-border-light bg-white flex items-center justify-center hover:bg-gray-50 transition-colors"
                    @click="goBack">
                    <ArrowLeftIcon class="w-4 h-4 text-text-secondary" />
                </button>
                <div>
                    <h2 class="text-lg font-bold text-text-primary">Student Mood Report</h2>
                    <p class="text-xs text-text-muted">
                        {{ studentReport.name }} · {{ studentReport.program }}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
                <div class="flex items-center gap-5 flex-wrap">
                    <div
                        class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center text-lg font-bold text-text-primary shrink-0">
                        {{ studentReport.initials }}
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-10 gap-y-3 flex-1">
                        <div>
                            <p class="text-xs text-text-muted">Name Of Student</p>
                            <p class="text-sm font-semibold text-text-primary mt-0.5">{{ studentReport.full_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted">Student Number</p>
                            <p class="text-sm font-semibold text-text-primary mt-0.5">{{ studentReport.student_number }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted">Year Level</p>
                            <p class="text-sm font-semibold text-text-primary mt-0.5">{{ studentReport.year_level }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted">Program</p>
                            <p class="text-sm font-semibold text-text-primary mt-0.5">{{ studentReport.program }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mood Summary + Summary Report stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Mood Summary horizontal bars -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-border-light shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-text-primary mb-5">Mood Summary</h3>

                    <div class="space-y-4">
                        <div v-for="item in moodBars" :key="item.mood" class="flex items-center gap-4">
                            <span class="text-sm text-text-secondary w-16 shrink-0">{{ item.mood }}</span>
                            <div class="flex-1 h-6 rounded-md bg-gray-100 overflow-hidden">
                                <div :class="['h-full rounded-md transition-all duration-500', MOOD_STYLE[item.mood]?.bar]"
                                    :style="{ width: item.pct + '%' }" />
                            </div>
                            <span class="text-sm font-semibold text-text-primary w-4 shrink-0 text-right">
                                {{ item.value }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mt-5 flex-wrap">
                        <div v-for="item in moodBars" :key="item.mood" class="flex items-center gap-1.5">
                            <span :class="['w-2.5 h-2.5 rounded-full', MOOD_STYLE[item.mood]?.dot]" />
                            <span class="text-xs text-text-muted">{{ item.mood }} {{ item.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Summary stats -->
                <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-text-primary mb-5">Summary Report</h3>

                    <div class="space-y-5">
                        <div>
                            <p class="text-xs text-text-muted">Total Mood Logs</p>
                            <p class="text-3xl font-extrabold text-text-primary mt-1">
                                {{ studentReport.summary_stats.total_mood_logs }}
                            </p>
                        </div>
                        <div class="border-t border-border-light" />
                        <div>
                            <p class="text-xs text-text-muted">Total Posts</p>
                            <p class="text-3xl font-extrabold text-text-primary mt-1">
                                {{ studentReport.summary_stats.total_posts }}
                            </p>
                        </div>
                        <div class="border-t border-border-light" />
                        <div>
                            <p class="text-xs text-text-muted">Flagged Posts</p>
                            <p class="text-3xl font-extrabold text-red-500 mt-1">
                                {{ studentReport.summary_stats.flagged_posts }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mood Trend chart (extracted component) -->
            <MoodTrendChart :data="studentReport.trend_data" :trend="studentReport.trend"
                :trend-days="filters.trendDays" @update:trend-days="onTrendDaysChange" />

            <!-- Recent Mood Logs -->
            <div class="bg-white rounded-2xl border border-border-light shadow-sm">
                <div class="px-6 py-4 border-b border-border-light">
                    <h3 class="text-sm font-semibold text-text-primary">Recent Mood Logs</h3>
                </div>

                <div class="divide-y divide-border-light">
                    <div v-for="log in studentReport.recent_logs" :key="log.id"
                        class="px-6 py-4 flex items-center gap-4">
                        <span
                            :class="['px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 w-20 text-center', LOG_TAG[log.mood] ?? 'bg-gray-100 text-gray-600']">
                            {{ log.mood }}
                        </span>
                        <p class="flex-1 text-sm text-text-secondary">{{ log.content }}</p>
                        <span class="text-xs text-text-muted shrink-0">{{ log.date }}</span>
                    </div>

                    <div v-if="studentReport.recent_logs.length === 0"
                        class="px-6 py-12 text-center text-sm text-text-muted">
                        No mood logs available.
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
