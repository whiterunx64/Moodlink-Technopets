<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MoodTrendLineChart from '@/Components/SummaryReports/MoodTrendLineChart.vue';
import PeriodFilter from '@/Components/SummaryReports/PeriodFilter.vue';
import type { MoodTrendPoint, StudentMoodReport, SummaryPeriod } from '@/types';

const props = withDefaults(
    defineProps<{
        studentReport: StudentMoodReport;
        filters: { period: SummaryPeriod; trendDays: number };
        from?: 'concern' | 'program';
    }>(),
    { from: 'program' },
);

// Bar and dot colors for the mood summary chart.
const MOOD_STYLE: Record<string, { bar: string; dot: string }> = {
    Excited: { bar: 'bg-amber-400', dot: 'bg-amber-400' },
    Content: { bar: 'bg-green-500', dot: 'bg-green-500' },
    Stressed: { bar: 'bg-red-400', dot: 'bg-red-400' },
    Drained: { bar: 'bg-slate-400', dot: 'bg-slate-400' },
};

const MOOD_TAG: Record<string, string> = {
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

// Download the report as a PDF (server-rendered), carrying the current filters.
function exportPdf() {
    const url = route('reports.students.pdf', props.studentReport.id) as string;
    const params = new URLSearchParams({
        period: props.filters.period,
        trendDays: String(props.filters.trendDays),
    });
    window.open(`${url}?${params.toString()}`, '_blank');
}

function onTrendDaysChange(days: number) {
    router.get(
        route('reports.students.show', props.studentReport.id),
        { period: props.filters.period, trendDays: days },
        { preserveState: true, replace: true, only: ['studentReport', 'filters'] },
    );
}

const cameFromConcern = computed(() => props.from === 'concern');

const backLabel = computed(() =>
    cameFromConcern.value ? 'Back to Students of Concern' : 'Back to Program Report',
);

function goBack() {
    if (cameFromConcern.value) {
        router.get(route('reports.index'), {
            period: props.filters.period,
            tab: 'studentsOfConcern',
        });
        return;
    }

    router.get(route('reports.programs.show', props.studentReport.program), {
        period: props.filters.period,
    });
}

// The day + mood whose posts are shown in the popup, set on a chart click.
const selectedDay = ref<MoodTrendPoint | null>(null);
const selectedMood = ref<string | null>(null);

function showDayPosts(payload: { point: MoodTrendPoint; mood: string }) {
    selectedDay.value = payload.point;
    selectedMood.value = payload.mood;
}

function closeDayPosts() {
    selectedDay.value = null;
    selectedMood.value = null;
}

// Only the clicked mood's posts for that day.
const selectedPosts = computed(() =>
    selectedDay.value
        ? selectedDay.value.posts.filter((p) => p.mood === selectedMood.value)
        : [],
);
</script>

<template>

    <Head title="Student Mood Report" />

    <AdminLayout title="Student Mood Report">
        <div class="space-y-5">

            <PeriodFilter :model-value="filters.period" @update:model-value="onPeriodChange"
                @export="exportPdf" />

            <div class="flex flex-col gap-3">
                <button type="button" :aria-label="backLabel"
                    class="inline-flex w-fit items-center gap-1.5 text-xs font-medium text-text-muted hover:text-text-primary transition-colors"
                    @click="goBack">
                    <ArrowLeftIcon class="w-4 h-4" />
                    {{ backLabel }}
                </button>
                <div>
                    <h2 class="text-lg font-bold text-text-primary">Student Mood Report</h2>
                    <p class="text-xs text-text-muted">
                        {{ studentReport.name }} · {{ studentReport.program }}
                    </p>
                </div>
            </div>

            <div class="bg-white border border-border-light shadow-sm p-6">
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
                <div class="lg:col-span-2 bg-white border border-border-light shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-text-primary mb-5">Mood Entries Data</h3>

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
                <div class="bg-white border border-border-light shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-text-primary mb-5">Summary Report</h3>

                    <div class="space-y-5">
                        <div>
                            <p class="text-xs text-text-muted">Total Mood Entries</p>
                            <p class="text-3xl font-extrabold text-text-primary mt-1">
                                {{ studentReport.summary_stats.total_mood_entries }}
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

            <!-- Mood Trend line chart — click a point to view that day's posts -->
            <MoodTrendLineChart :data="studentReport.trend_data"
                :trend-days="filters.trendDays" @update:trend-days="onTrendDaysChange"
                @select-day="showDayPosts" />

            <!-- Recent Mood Logs -->
            <div class="bg-white border border-border-light shadow-sm">
                <div class="px-6 py-4 border-b border-border-light">
                    <h3 class="text-sm font-semibold text-text-primary">Recent Journal Entries</h3>
                </div>

                <div class="divide-y divide-border-light">
                    <div v-for="entry in studentReport.recent_entries" :key="entry.id"
                        class="px-6 py-4 flex items-center gap-4">
                        <span
                            :class="['px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 w-20 text-center', MOOD_TAG[entry.mood] ?? 'bg-gray-100 text-gray-600']">
                            {{ entry.mood }}
                        </span>
                        <p v-if="entry.content" class="flex-1 text-sm text-text-secondary">
                            {{ entry.content }}
                        </p>
                        <p v-else class="flex-1 text-sm italic text-text-muted">
                            No journal written for this entry.
                        </p>
                        <span class="text-xs text-text-muted shrink-0">{{ entry.date }}</span>
                    </div>

                    <div v-if="studentReport.recent_entries.length === 0"
                        class="px-6 py-12 text-center text-sm text-text-muted">
                        No journal entries available.
                    </div>
                </div>
            </div>

        </div>

        <!-- Day posts popup — opened by clicking a point on the trend chart -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="selectedDay"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="closeDayPosts" />

                    <div class="relative w-full max-w-lg bg-white p-6 shadow-xl">
                        <button type="button" aria-label="Close"
                            class="absolute right-4 top-4 p-1 text-text-muted transition-colors hover:bg-gray-100"
                            @click="closeDayPosts">
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <h3 class="text-base font-bold text-text-primary">
                            {{ selectedMood }} posts on {{ selectedDay.date }}
                        </h3>
                        <p class="mb-4 text-xs text-text-muted">
                            {{ selectedPosts.length }}
                            post{{ selectedPosts.length === 1 ? '' : 's' }}
                        </p>

                        <div class="max-h-96 space-y-3 overflow-y-auto pr-1">
                            <div v-for="post in selectedPosts" :key="post.id"
                                class="rounded-xl border border-border-light p-4">
                                <div class="mb-2 flex items-center gap-3">
                                    <span
                                        :class="['w-20 shrink-0 rounded-full px-2.5 py-1 text-center text-xs font-semibold', MOOD_TAG[post.mood] ?? 'bg-gray-100 text-gray-600']">
                                        {{ post.mood }}
                                    </span>
                                    <span class="text-xs text-text-muted">{{ post.time }}</span>
                                </div>
                                <p v-if="post.content"
                                    class="whitespace-pre-line text-sm text-text-primary">
                                    {{ post.content }}
                                </p>
                                <p v-else class="text-sm italic text-text-muted">
                                    (no text)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
