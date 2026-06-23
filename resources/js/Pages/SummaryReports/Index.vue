<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
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
    XMarkIcon,
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

// Only the active tab's data is sent by the server; the others fall back to
// empty defaults so the inactive tab templates stay type-safe.
const props = withDefaults(defineProps<{
    filters: SummaryFilters;
    overview?: SummaryOverview;
    sections?: SectionSummary[];
    atRiskStudents?: AtRiskStudent[];
}>(), {
    overview: () => ({ totalMoodLogs: 0, avgDailyLogs: 0, atRiskStudents: 0, appointmentsSet: 0, distribution: [] }),
    sections: () => [],
    atRiskStudents: () => [],
});

const MOOD_STYLE: Record<string, { bar: string; text: string; dot: string; tag: string }> = {
    Excited: { bar: 'bg-green-500', text: 'text-green-600', dot: 'bg-green-500', tag: 'bg-green-100 text-green-700' },
    Content: { bar: 'bg-blue-500', text: 'text-blue-600', dot: 'bg-blue-500', tag: 'bg-blue-100 text-blue-700' },
    Stressed: { bar: 'bg-orange-400', text: 'text-orange-500', dot: 'bg-orange-400', tag: 'bg-orange-100 text-orange-700' },
    Drained: { bar: 'bg-red-400', text: 'text-red-500', dot: 'bg-red-400', tag: 'bg-red-100 text-red-700' },
};

function onPeriodChange(p: SummaryPeriod) {
    router.get(route('summary-reports.index'), { period: p, tab: activeTab.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const activeTab = computed<string>(() => props.filters.tab ?? 'overview');

const tabs: Array<{ key: string; label: string; icon: Component }> = [
    { key: 'overview', label: 'Overview', icon: GlobeAltIcon },
    { key: 'sections', label: 'Section Reports', icon: ListBulletIcon },
    { key: 'at-risk', label: 'At-Risk Students', icon: ExclamationTriangleIcon },
];

function changeTab(tab: string) {
    if (tab === activeTab.value) {
        return;
    }

    router.get(route('summary-reports.index'), { period: props.filters.period, tab }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function openSection(section: string) {
    router.get(route('summary-reports.section-aggregated-report', section), { period: props.filters.period });
}

const showConsultModal = ref(false);
const consultStudent = ref<AtRiskStudent | null>(null);
const consultForm = useForm({ date: '', start_time: '' });

function openConsult(student: AtRiskStudent) {
    consultStudent.value = student;
    consultForm.reset();
    consultForm.clearErrors();
    showConsultModal.value = true;
}

function submitConsult() {
    if (consultStudent.value === null) {
        return;
    }

    consultForm.post(route('summary-reports.consult', consultStudent.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConsultModal.value = false;
            consultForm.reset();
        },
    });
}

function miniBarWidth(count: number, total: number): string {
    return total > 0 ? `${Math.round((count / total) * 100)}%` : '0%';
}
</script>

<template>

    <Head title="Summary Reports" />

    <AdminLayout title="Summary Reports">
        <div class="space-y-5">

            <PeriodFilter :model-value="filters.period" @update:model-value="onPeriodChange" />

            <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1 w-fit">
                <button v-for="tab in tabs" :key="tab.key" type="button" :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    activeTab === tab.key
                        ? 'bg-white text-text-primary shadow-sm'
                        : 'text-text-muted hover:text-text-secondary',
                ]" @click="changeTab(tab.key)">
                    <component :is="tab.icon" class="w-4 h-4" />
                    {{ tab.label }}
                </button>
            </div>

            <template v-if="activeTab === 'overview'">

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

                <MoodDistributionCard :distribution="overview.distribution" :period="filters.period" />
            </template>

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
                            {{ atRiskStudents.length }} At Risk
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
                                    <p class="text-sm font-semibold text-red-500">{{ student.daysAtRisk }} days at risk
                                    </p>
                                    <p class="text-xs text-text-muted">Last log: {{ student.lastLog }}</p>
                                </div>

                                <button v-if="!student.hasConsultation" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sidebar text-white text-xs font-semibold hover:bg-sidebar/90 transition-colors"
                                    @click="openConsult(student)">
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

        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showConsultModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="showConsultModal = false" />

                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-7">
                        <button type="button"
                            class="absolute top-4 right-4 p-1 rounded-full text-text-muted hover:bg-gray-100 transition-colors"
                            @click="showConsultModal = false">
                            <XMarkIcon class="w-4 h-4" />
                        </button>

                        <h2 class="text-base font-bold text-text-primary mb-1 text-center">Set Consultation Schedule</h2>
                        <p v-if="consultStudent" class="text-xs text-text-muted mb-6 text-center">
                            {{ consultStudent.name }} · {{ consultStudent.studentNumber }}
                        </p>

                        <form @submit.prevent="submitConsult" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-text-secondary mb-1.5">Date</label>
                                <input v-model="consultForm.date" type="date" required
                                    class="w-full border border-border-light rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar/30 focus:border-sidebar" />
                                <p v-if="consultForm.errors.date" class="text-xs text-red-500 mt-1">{{
                                    consultForm.errors.date }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-text-secondary mb-1.5">Start time</label>
                                <input v-model="consultForm.start_time" type="time" required
                                    class="w-full border border-border-light rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar/30 focus:border-sidebar" />
                                <p v-if="consultForm.errors.start_time" class="text-xs text-red-500 mt-1">{{
                                    consultForm.errors.start_time }}</p>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="button"
                                    class="flex-1 py-2.5 rounded-xl border border-border-light text-sm font-medium text-text-secondary hover:bg-gray-50 transition-colors"
                                    @click="showConsultModal = false">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="consultForm.processing"
                                    class="flex-1 py-2.5 rounded-xl bg-sidebar text-white text-sm font-semibold hover:bg-sidebar/90 transition-colors disabled:opacity-60">
                                    {{ consultForm.processing ? 'Scheduling…' : 'Schedule' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
