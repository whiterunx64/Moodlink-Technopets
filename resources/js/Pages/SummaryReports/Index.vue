<script setup lang="ts">
import MoodDistributionCard from '@/Components/SummaryReports/MoodDistributionCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    AtRiskStudent,
    ProgramSummary,
    SummaryFilters,
    SummaryOverview,
    SummaryPeriod,
} from '@/types';
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
import { usePolling } from '@/composables/usePolling';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, type Component } from 'vue';

const PERIODS: { key: SummaryPeriod; label: string }[] = [
    { key: 'this_week', label: 'This Week' },
    { key: 'this_month', label: 'This Month' },
    { key: 'all_time', label: 'All Time' },
];

// Only the active tab's data is sent by the server; the others fall back to
// empty defaults so the inactive tab templates stay type-safe.
const props = withDefaults(
    defineProps<{
        filters: SummaryFilters;
        overview?: SummaryOverview;
        programs?: ProgramSummary[];
        atRiskStudents?: AtRiskStudent[];
        availableSlots?: ConsultationSlot[];
    }>(),
    {
        overview: () => ({
            total_mood_logs: 0,
            avg_daily_logs: 0,
            at_risk_students: 0,
            appointments_set: 0,
            distribution: [],
        }),
        programs: () => [],
        atRiskStudents: () => [],
        availableSlots: () => [],
    },
);

type ConsultationSlot = {
    id: number;
    date: string;
    time: string;
};

const MOOD_STYLE: Record<
    string,
    { bar: string; text: string; dot: string; tag: string }
> = {
    Excited: {
        bar: 'bg-green-500',
        text: 'text-green-600',
        dot: 'bg-green-500',
        tag: 'bg-green-100 text-green-700',
    },
    Content: {
        bar: 'bg-blue-500',
        text: 'text-blue-600',
        dot: 'bg-blue-500',
        tag: 'bg-blue-100 text-blue-700',
    },
    Stressed: {
        bar: 'bg-orange-400',
        text: 'text-orange-500',
        dot: 'bg-orange-400',
        tag: 'bg-orange-100 text-orange-700',
    },
    Drained: {
        bar: 'bg-red-400',
        text: 'text-red-500',
        dot: 'bg-red-400',
        tag: 'bg-red-100 text-red-700',
    },
};

function onPeriodChange(p: SummaryPeriod) {
    router.get(
        route('reports.index'),
        { period: p, tab: activeTab.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

const activeTab = computed<string>(() => props.filters.tab ?? 'overview');

// Refresh only the active tab's dataset; period/tab filters stay client-driven.
const TAB_PROPS: Record<string, string[]> = {
    overview: ['overview'],
    programs: ['programs'],
    studentsOfConcern: ['atRiskStudents', 'availableSlots'],
};

usePolling(
    () => {
        const only = TAB_PROPS[activeTab.value] ?? ['overview'];
        router.reload({ only, preserveUrl: true });
    },
    { interval: 30_000 },
);

const tabs: Array<{ key: string; label: string; icon: Component }> = [
    { key: 'overview', label: 'Overview', icon: GlobeAltIcon },
    { key: 'programs', label: 'Program Reports', icon: ListBulletIcon },
    {
        key: 'studentsOfConcern',
        label: 'Students of Concern',
        icon: ExclamationTriangleIcon,
    },
];

function changeTab(tab: string) {
    if (tab === activeTab.value) {
        return;
    }

    router.get(
        route('reports.index'),
        { period: props.filters.period, tab },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function openProgram(program: string) {
    router.get(route('reports.programs.show', program), {
        period: props.filters.period,
    });
}

function openStudentReport(studentId: number) {
    router.get(route('reports.students.show', studentId), {
        period: props.filters.period,
        from: 'concern',
    });
}

const showConsultModal = ref(false);
const consultStudent = ref<AtRiskStudent | null>(null);
const consultForm = useForm<{ slot_id: number | null }>({ slot_id: null });

// Available slots grouped by day, so the picker reads as a date → times list.
const slotsByDate = computed<{ date: string; slots: ConsultationSlot[] }[]>(
    () => {
        const groups = new Map<string, ConsultationSlot[]>();

        for (const slot of props.availableSlots) {
            const bucket = groups.get(slot.date) ?? [];
            bucket.push(slot);
            groups.set(slot.date, bucket);
        }

        return [...groups.entries()].map(([date, slots]) => ({ date, slots }));
    },
);

const selectedSlot = computed<ConsultationSlot | null>(
    () =>
        props.availableSlots.find((s) => s.id === consultForm.slot_id) ?? null,
);

function openConsult(student: AtRiskStudent) {
    // No slots to book into — send the admin to Appointments to create some.
    if (props.availableSlots.length === 0) {
        router.get(route('appointments.index'));
        return;
    }

    consultStudent.value = student;
    consultForm.reset();
    consultForm.clearErrors();
    showConsultModal.value = true;
}

function submitConsult() {
    if (consultStudent.value === null || consultForm.slot_id === null) {
        return;
    }

    consultForm.post(route('reports.consult', consultStudent.value.id), {
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
            <div class="flex w-fit items-center gap-1 bg-gray-100 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    :class="[
                        'flex cursor-pointer items-center gap-2 px-4 py-2 text-sm font-medium transition-all',
                        activeTab === tab.key
                            ? 'text-text-primary bg-white shadow-sm hover:bg-[#fef]'
                            : 'text-text-muted hover:text-text-secondary hover:bg-black/5',
                        //tab.key == 'studentsOfConcern' ? 'hidden' : '',
                    ]"
                    @click="changeTab(tab.key)"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    {{ tab.label }}
                </button>
            </div>

            <template v-if="activeTab === 'overview'">
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        class="border-border-light flex items-center gap-4 border bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center bg-blue-50"
                        >
                            <HeartIcon class="h-6 w-6 text-blue-400" />
                        </div>
                        <div>
                            <p
                                class="text-text-primary text-3xl font-extrabold"
                            >
                                {{ overview.total_mood_logs }}
                            </p>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Total Mood Logs
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-border-light flex items-center gap-4 border bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center bg-green-50"
                        >
                            <ArrowTrendingUpIcon
                                class="h-6 w-6 text-green-400"
                            />
                        </div>
                        <div>
                            <p
                                class="text-text-primary text-3xl font-extrabold"
                            >
                                {{ overview.avg_daily_logs }}
                            </p>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Avg Daily Logs
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-border-light flex items-center gap-4 border bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center bg-red-50"
                        >
                            <ExclamationCircleIcon
                                class="h-6 w-6 text-red-400"
                            />
                        </div>
                        <div>
                            <p
                                class="text-text-primary text-3xl font-extrabold"
                            >
                                {{ overview.at_risk_students }}
                            </p>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Students of Concern
                            </p>
                        </div>
                    </div>

                    <div
                        class="border-border-light flex items-center gap-4 border bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center bg-purple-50"
                        >
                            <CalendarDaysIcon class="h-6 w-6 text-purple-400" />
                        </div>
                        <div>
                            <p
                                class="text-text-primary text-3xl font-extrabold"
                            >
                                {{ overview.appointments_set }}
                            </p>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Appointments Set
                            </p>
                        </div>
                    </div>
                </div>

                <MoodDistributionCard
                    :distribution="overview.distribution"
                    :period="filters.period"
                    @update:period="onPeriodChange"
                />
            </template>

            <template v-else-if="activeTab === 'programs'">
                <div class="border-border-light border bg-white shadow-sm">
                    <div
                        class="border-border-light flex flex-wrap items-center justify-between gap-3 border-b px-6 py-4"
                    >
                        <div>
                            <h3
                                class="text-text-primary text-base font-semibold"
                            >
                                Program-Based Mood Reports
                            </h3>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Click a program to view its students and mood
                                details
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                v-for="p in PERIODS"
                                :key="p.key"
                                type="button"
                                :class="[
                                    'border px-3.5 py-1.5 text-xs font-medium transition-colors',
                                    filters.period === p.key
                                        ? 'bg-sidebar border-sidebar text-white'
                                        : 'text-text-secondary border-border-light bg-white hover:bg-gray-50',
                                ]"
                                @click="onPeriodChange(p.key)"
                            >
                                {{ p.label }}
                            </button>
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-border-light border-b">
                                <th
                                    class="text-text-muted px-6 py-3 text-left text-xs font-medium"
                                >
                                    Program
                                </th>
                                <th
                                    class="text-text-muted px-4 py-3 text-left text-xs font-medium"
                                >
                                    Total
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-green-500"
                                >
                                    Excited
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-blue-500"
                                >
                                    Content
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-orange-500"
                                >
                                    Stressed
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-red-500"
                                >
                                    Drained
                                </th>
                                <th
                                    class="text-text-muted px-4 py-3 text-left text-xs font-medium"
                                >
                                    Concern Quantity
                                </th>
                                <th
                                    class="text-text-muted px-4 py-3 text-left text-xs font-medium"
                                >
                                    Distribution
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-border-light divide-y">
                            <tr
                                v-for="sec in programs"
                                :key="sec.program"
                                class="cursor-pointer transition-colors hover:bg-gray-50"
                                @click="openProgram(sec.program)"
                            >
                                <td
                                    class="text-sidebar px-6 py-4 font-semibold"
                                >
                                    {{ sec.program }}
                                </td>
                                <td class="text-text-primary px-4 py-4">
                                    {{ sec.total }}
                                </td>
                                <td
                                    class="px-4 py-4 font-semibold text-green-600"
                                >
                                    {{ sec.excited }}
                                </td>
                                <td
                                    class="px-4 py-4 font-semibold text-blue-600"
                                >
                                    {{ sec.content }}
                                </td>
                                <td
                                    class="px-4 py-4 font-semibold text-orange-500"
                                >
                                    {{ sec.stressed }}
                                </td>
                                <td
                                    class="px-4 py-4 font-semibold text-red-500"
                                >
                                    {{ sec.drained }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        v-if="sec.at_risk > 0"
                                        class="inline-flex h-7 w-7 items-center justify-center bg-red-100 text-xs font-bold text-red-600"
                                    >
                                        {{ sec.at_risk }}
                                    </span>
                                    <span v-else class="text-text-muted"
                                        >—</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex h-2 w-28 overflow-hidden">
                                        <div
                                            class="h-full bg-[#0fdf58]"
                                            :style="{
                                                width: miniBarWidth(
                                                    sec.excited,
                                                    sec.total,
                                                ),
                                            }"
                                        />
                                        <div
                                            class="h-full bg-[#8da9e4]"
                                            :style="{
                                                width: miniBarWidth(
                                                    sec.content,
                                                    sec.total,
                                                ),
                                            }"
                                        />
                                        <div
                                            class="h-full bg-[#f2c94c]"
                                            :style="{
                                                width: miniBarWidth(
                                                    sec.stressed,
                                                    sec.total,
                                                ),
                                            }"
                                        />
                                        <div
                                            class="h-full bg-[#fb5737]"
                                            :style="{
                                                width: miniBarWidth(
                                                    sec.drained,
                                                    sec.total,
                                                ),
                                            }"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="programs.length === 0">
                                <td
                                    colspan="8"
                                    class="text-text-muted px-6 py-16 text-center text-sm"
                                >
                                    No program data available for this period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else-if="activeTab === 'studentsOfConcern'">
                <div class="border-border-light border bg-white shadow-sm">
                    <div
                        class="border-border-light flex flex-wrap items-center justify-between gap-3 border-b px-6 py-4"
                    >
                        <div>
                            <h3
                                class="text-text-primary text-base font-semibold"
                            >
                                Students of Concern
                            </h3>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Students with consistently negative mood
                                patterns
                            </p>
                        </div>
                        <span
                            v-if="atRiskStudents.length > 0"
                            class="bg-red-100 px-3 py-1 text-xs font-semibold text-red-600"
                        >
                            {{ atRiskStudents.length }} Students
                        </span>
                    </div>

                    <div class="divide-border-light divide-y">
                        <div
                            v-for="student in atRiskStudents"
                            :key="student.id"
                            class="flex items-center gap-4 px-6 py-4"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center bg-red-100 text-red-400"
                            >
                                <UserIcon class="h-5 w-5" />
                            </div>

                            <div
                                class="min-w-0 flex-1 cursor-pointer"
                                role="button"
                                tabindex="0"
                                @click="openStudentReport(student.id)"
                                @keydown.enter="openStudentReport(student.id)"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-text-primary hover:text-sidebar text-sm font-semibold hover:underline"
                                        >{{ student.name }}</span
                                    >
                                    <span
                                        v-if="student.crisis_count > 0"
                                        class="inline-flex items-center gap-1 bg-red-600 px-2 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase"
                                        :title="
                                            student.crisis_excerpt
                                                ? `Journal: “${student.crisis_excerpt}”`
                                                : 'Crisis language detected in journal'
                                        "
                                    >
                                        ⚠ Crisis language
                                        <span v-if="student.crisis_count > 1"
                                            >({{ student.crisis_count }})</span
                                        >
                                    </span>
                                    <span class="text-text-muted text-xs"
                                        >· {{ student.student_number }} ·
                                        {{ student.program }}</span
                                    >
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-1.5"
                                >
                                    <span
                                        v-for="(
                                            count, mood
                                        ) in student.warning_mood_counts"
                                        :key="mood"
                                        :class="[
                                            'px-2 py-0.5 text-xs font-medium',
                                            MOOD_STYLE[mood]?.tag ??
                                                'bg-gray-100 text-gray-600',
                                        ]"
                                    >
                                        {{ mood }} {{ count }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-4">
                                <div class="text-right">
                                    <p
                                        class="text-sm font-semibold text-red-500"
                                    >
                                        Of concern for
                                        {{ student.time_at_risk }}
                                    </p>
                                    <p class="text-text-muted text-xs">
                                        Last log: {{ student.last_log }}
                                    </p>
                                </div>

                                <button
                                    v-if="!student.has_consultation"
                                    type="button"
                                    class="bg-sidebar hover:bg-sidebar/90 inline-flex cursor-pointer items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white transition-colors"
                                    @click="openConsult(student)"
                                >
                                    <ChatBubbleLeftEllipsisIcon
                                        class="h-3.5 w-3.5"
                                    />
                                    Consult
                                </button>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-700"
                                >
                                    <CheckIcon class="h-3.5 w-3.5" />
                                    Consultation Set
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="atRiskStudents.length === 0"
                            class="text-text-muted px-6 py-16 text-center text-sm"
                        >
                            No students of concern for this period.
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showConsultModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="absolute inset-0 bg-black/30"
                        @click="showConsultModal = false"
                    />

                    <div
                        class="relative w-full max-w-md bg-white p-7 shadow-xl"
                    >
                        <button
                            type="button"
                            class="text-text-muted absolute top-4 right-4 p-1 transition-colors hover:bg-gray-100"
                            @click="showConsultModal = false"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <h2
                            class="text-text-primary mb-1 text-center text-base font-bold"
                        >
                            Set Consultation Schedule
                        </h2>
                        <p
                            v-if="consultStudent"
                            class="text-text-muted mb-1 text-center text-xs"
                        >
                            {{ consultStudent.name }} ·
                            {{ consultStudent.student_number }}
                        </p>
                        <p class="mb-6 text-center text-xs text-gray-400">
                            Refer this student into an available consultation
                            slot.
                        </p>

                        <form @submit.prevent="submitConsult" class="space-y-5">
                            <div>
                                <label
                                    class="text-text-secondary mb-2 block text-xs font-medium"
                                >
                                    Available Slots
                                    <span
                                        v-if="selectedSlot"
                                        class="text-sidebar ml-2 font-semibold"
                                    >
                                        · {{ selectedSlot.date }},
                                        {{ selectedSlot.time }}
                                    </span>
                                </label>

                                <div
                                    v-if="slotsByDate.length > 0"
                                    class="max-h-64 space-y-4 overflow-y-auto pr-1"
                                >
                                    <div
                                        v-for="group in slotsByDate"
                                        :key="group.date"
                                    >
                                        <p
                                            class="text-text-muted mb-2 text-xs font-semibold"
                                        >
                                            {{ group.date }}
                                        </p>
                                        <div class="grid grid-cols-3 gap-2">
                                            <button
                                                v-for="slot in group.slots"
                                                :key="slot.id"
                                                type="button"
                                                :class="[
                                                    'border py-2 text-xs font-medium transition-colors',
                                                    consultForm.slot_id ===
                                                    slot.id
                                                        ? 'bg-sidebar border-sidebar text-white'
                                                        : 'text-text-secondary border-border-light hover:border-sidebar/40 hover:bg-sidebar/5 bg-white',
                                                ]"
                                                @click="
                                                    consultForm.slot_id =
                                                        slot.id
                                                "
                                            >
                                                {{ slot.time }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-if="consultForm.errors.slot_id"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ consultForm.errors.slot_id }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 pt-1">
                                <button
                                    type="button"
                                    class="border-border-light text-text-secondary flex-1 border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
                                    @click="showConsultModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="
                                        consultForm.processing ||
                                        consultForm.slot_id === null
                                    "
                                    class="bg-sidebar hover:bg-sidebar/90 flex-1 py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60"
                                >
                                    {{
                                        consultForm.processing
                                            ? 'Scheduling…'
                                            : 'Schedule'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
