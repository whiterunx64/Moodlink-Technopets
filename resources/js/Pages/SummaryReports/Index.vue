<script setup lang="ts">
import { ref, type Component } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowTrendingUpIcon,
    CalendarDaysIcon,
    ChatBubbleLeftEllipsisIcon,
    CheckIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    GlobeAltIcon,
    HeartIcon,
    ListBulletIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MoodDistributionCard from '@/Components/SummaryReports/MoodDistributionCard.vue';
import PeriodFilter from '@/Components/SummaryReports/PeriodFilter.vue';
import type {
    AtRiskStudent,
    SectionSummary,
    SummaryFilters,
    SummaryOverview,
    SummaryPeriod,
} from '@/types';

const props = defineProps<{
    overview: SummaryOverview;
    sections: SectionSummary[];
    atRiskStudents: AtRiskStudent[];
    filters: SummaryFilters;
}>();

const MOOD_STYLE: Record<string, { bar: string; text: string; dot: string; tag: string }> = {
    Excited: { bar: 'bg-green-500', text: 'text-green-600', dot: 'bg-green-500', tag: 'bg-green-100 text-green-700' },
    Content: { bar: 'bg-blue-500', text: 'text-blue-600', dot: 'bg-blue-500', tag: 'bg-blue-100 text-blue-700' },
    Stressed: { bar: 'bg-orange-400', text: 'text-orange-500', dot: 'bg-orange-400', tag: 'bg-orange-100 text-orange-700' },
    Drained: { bar: 'bg-red-400', text: 'text-red-500', dot: 'bg-red-400', tag: 'bg-red-100 text-red-700' },
};

// ── Period filter ─────────────────────────────────────────────────────────────
function onPeriodChange(p: SummaryPeriod) {
    router.get(route('summary-reports.index'), { period: p, tab: activeTab.value }, {
        preserveState: true,
        replace: true,
    });
}

// ── Tabs ──────────────────────────────────────────────────────────────────────
const activeTab = ref<string>(props.filters.tab ?? 'overview');

const tabs: Array<{ key: string; label: string; icon: Component }> = [
    { key: 'overview', label: 'Overview', icon: GlobeAltIcon },
    { key: 'sections', label: 'Section Reports', icon: ListBulletIcon },
    { key: 'at-risk', label: 'At-Risk Students', icon: ExclamationTriangleIcon },
];

// ── Section navigation ────────────────────────────────────────────────────────
function openSection(section: string) {
    router.get(route('summary-reports.section-aggregated-report', section), { period: props.filters.period });
}

// ── Consult action ────────────────────────────────────────────────────────────
function consult(student: AtRiskStudent) {
    router.post(route('summary-reports.consult', student.id), {}, {
        preserveScroll: true,
        only: ['atRiskStudents'],
    });
}

// ── Mini bar helper ───────────────────────────────────────────────────────────
function miniBarWidth(count: number, total: number): string {
    return total > 0 ? `${Math.round((count / total) * 100)}%` : '0%';
}
</script>

<template>

    <Head title="Summary Reports" />

    <AdminLayout title="Summary Reports">
        <div class="space-y-5">

            <!-- Period filter + Export PDF -->
            <PeriodFilter :model-value="filters.period" @update:model-value="onPeriodChange" />

            <!-- Tab navigation -->
            <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1 w-fit">
                <button v-for="tab in tabs" :key="tab.key" type="button" :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    activeTab === tab.key
                        ? 'bg-white text-text-primary shadow-sm'
                        : 'text-text-muted hover:text-text-secondary',
                ]" @click="activeTab = tab.key">
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── Overview tab ──────────────────────────────────────────── -->
            <template v-if="activeTab === 'overview'">

                <!-- Stat cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                            <HeartIcon class="w-6 h-6 text-blue-400" />
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-text-primary">{{ overview.totalMoodLogs }}</p>
                            <p class="text-xs text-text-muted mt-0.5">Total Mood Logs</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                            <ArrowTrendingUpIcon class="w-6 h-6 text-green-400" />
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-text-primary">{{ overview.avgDailyLogs }}</p>
                            <p class="text-xs text-text-muted mt-0.5">Avg Daily Logs</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                            <ExclamationCircleIcon class="w-6 h-6 text-red-400" />
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-text-primary">{{ overview.atRiskStudents }}</p>
                            <p class="text-xs text-text-muted mt-0.5">At-Risk Students</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                            <CalendarDaysIcon class="w-6 h-6 text-purple-400" />
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-text-primary">{{ overview.appointmentsSet }}</p>
                            <p class="text-xs text-text-muted mt-0.5">Appointments Set</p>
                        </div>
                    </div>
                </div>

                <!-- Mood Distribution -->
                <MoodDistributionCard :distribution="overview.distribution" :period="filters.period" />
            </template>

            <!-- ── Section Reports tab ───────────────────────────────────── -->
            <template v-else-if="activeTab === 'sections'">
                <div class="bg-white rounded-2xl border border-border-light shadow-sm">
                    <div class="px-6 py-4 border-b border-border-light">
                        <h3 class="text-base font-semibold text-text-primary">Section-Based Mood Reports</h3>
                        <p class="text-xs text-text-muted mt-0.5">Click a section to view its students and mood details
                        </p>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border-light">
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-muted">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-muted">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-500">Excited</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-blue-500">Content</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-orange-500">Stressed</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-red-500">Drained</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-muted">At-Risk</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-text-muted">Distribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-light">
                            <tr v-for="sec in sections" :key="sec.section"
                                class="hover:bg-gray-50 cursor-pointer transition-colors"
                                @click="openSection(sec.section)">
                                <td class="px-6 py-4 font-semibold text-sidebar">{{ sec.section }}</td>
                                <td class="px-4 py-4 text-text-primary">{{ sec.total }}</td>
                                <td class="px-4 py-4 font-semibold text-green-600">{{ sec.excited }}</td>
                                <td class="px-4 py-4 font-semibold text-blue-600">{{ sec.content }}</td>
                                <td class="px-4 py-4 font-semibold text-orange-500">{{ sec.stressed }}</td>
                                <td class="px-4 py-4 font-semibold text-red-500">{{ sec.drained }}</td>
                                <td class="px-4 py-4">
                                    <span v-if="sec.atRisk > 0"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-600 font-bold text-xs">
                                        {{ sec.atRisk }}
                                    </span>
                                    <span v-else class="text-text-muted">—</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex h-2 w-28 rounded-full overflow-hidden">
                                        <div class="bg-green-500 h-full"
                                            :style="{ width: miniBarWidth(sec.excited, sec.total) }" />
                                        <div class="bg-blue-500 h-full"
                                            :style="{ width: miniBarWidth(sec.content, sec.total) }" />
                                        <div class="bg-orange-400 h-full"
                                            :style="{ width: miniBarWidth(sec.stressed, sec.total) }" />
                                        <div class="bg-red-400 h-full"
                                            :style="{ width: miniBarWidth(sec.drained, sec.total) }" />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="sections.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center text-sm text-text-muted">
                                    No section data available for this period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- ── At-Risk Students tab ──────────────────────────────────── -->
            <template v-else-if="activeTab === 'at-risk'">
                <div class="bg-white rounded-2xl border border-border-light shadow-sm">
                    <div class="px-6 py-4 border-b border-border-light flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-text-primary">At-Risk Students</h3>
                            <p class="text-xs text-text-muted mt-0.5">Students with consistently negative mood patterns
                            </p>
                        </div>
                        <span v-if="atRiskStudents.length > 0"
                            class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-semibold">
                            {{ atRiskStudents.length }} Flagged
                        </span>
                    </div>

                    <div class="divide-y divide-border-light">
                        <div v-for="student in atRiskStudents" :key="student.id"
                            class="px-6 py-4 flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0 text-red-400">
                                <UserIcon class="w-5 h-5" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-text-primary text-sm">{{ student.name }}</span>
                                    <span class="text-xs text-text-muted">· {{ student.studentNumber }} · {{
                                        student.section }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    <span v-for="mood in student.moods" :key="mood"
                                        :class="['px-2 py-0.5 rounded-full text-xs font-medium', MOOD_STYLE[mood]?.tag ?? 'bg-gray-100 text-gray-600']">
                                        {{ mood }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 shrink-0">
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-red-500">{{ student.daysFlagged }} days flagged
                                    </p>
                                    <p class="text-xs text-text-muted">Last log: {{ student.lastLog }}</p>
                                </div>

                                <button v-if="!student.hasConsultation" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sidebar text-white text-xs font-semibold hover:bg-sidebar/90 transition-colors"
                                    @click="consult(student)">
                                    <ChatBubbleLeftEllipsisIcon class="w-3.5 h-3.5" />
                                    Consult
                                </button>
                                <span v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-100 text-green-700 text-xs font-semibold">
                                    <CheckIcon class="w-3.5 h-3.5" />
                                    Consultation Set
                                </span>
                            </div>
                        </div>

                        <div v-if="atRiskStudents.length === 0" class="px-6 py-16 text-center text-sm text-text-muted">
                            No at-risk students for this period.
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </AdminLayout>
</template>
