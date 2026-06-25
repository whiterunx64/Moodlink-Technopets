<script setup lang="ts">
import { usePollingReload } from '@/composables/usePolling';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    Appointment,
    AppointmentFilters,
    AppointmentStudentProfile,
    AppointmentTab,
    AppointmentTabCounts,
    AvailableSlot,
} from '@/types';
import {
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const todayISO = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });

const TIME_SLOTS = [
    { value: '08:00', label: '8:00 AM' },
    { value: '09:00', label: '9:00 AM' },
    { value: '10:00', label: '10:00 AM' },
    { value: '11:00', label: '11:00 AM' },
    { value: '12:00', label: '12:00 PM' },
    { value: '13:00', label: '1:00 PM' },
    { value: '14:00', label: '2:00 PM' },
    { value: '15:00', label: '3:00 PM' },
    { value: '16:00', label: '4:00 PM' },
    { value: '17:00', label: '5:00 PM' },
    { value: '18:00', label: '6:00 PM' },
];

const props = defineProps<{
    appointments: Appointment[];
    tabCounts: AppointmentTabCounts;
    availableSlots: AvailableSlot[];
    filters: AppointmentFilters;
}>();

const TABS: { key: AppointmentTab; label: string }[] = [
    { key: 'requests', label: 'Requests' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'missed', label: 'Missed' },
    { key: 'history', label: 'History' },
    { key: 'rejected', label: 'Rejected' },
];

usePollingReload(['appointments', 'tabCounts', 'availableSlots']);

const activeTab = computed(() => props.filters.tab ?? 'requests');

function switchTab(tab: AppointmentTab) {
    router.get(
        route('appointments.index'),
        { tab },
        { preserveState: true, replace: true },
    );
}

const pendingAction = ref<{
    type: 'approve' | 'reject';
    id: number;
    name: string;
} | null>(null);

function approve(id: number) {
    router.patch(
        route('appointments.approve', id),
        {},
        { preserveScroll: true },
    );
}
function reject(id: number) {
    router.patch(
        route('appointments.reject', id),
        {},
        { preserveScroll: true },
    );
}
function complete(id: number) {
    router.patch(
        route('appointments.complete', id),
        {},
        { preserveScroll: true },
    );
}

function confirmAction(type: 'approve' | 'reject', apt: Appointment) {
    pendingAction.value = { type, id: apt.id, name: apt.student_name };
}

function executePending() {
    if (!pendingAction.value) return;
    const { type, id } = pendingAction.value;
    pendingAction.value = null;
    if (type === 'approve') approve(id);
    else reject(id);
}

const STATUS_BADGE: Record<string, string> = {
    Scheduled: 'bg-green-100 text-green-700',
    Completed: 'bg-amber-100 text-amber-700',
    Rejected: 'bg-red-100 text-red-600',
    Pending: 'bg-amber-50 text-amber-600',
    Missed: 'bg-purple-100 text-purple-700',
};

function formatDateTime(date: string, time: string) {
    return new Date(date + ' ' + time).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

const selectedStudent = ref<
    (AppointmentStudentProfile & { name: string }) | null
>(null);

function openProfile(apt: Appointment) {
    selectedStudent.value = { ...apt.student_profile, name: apt.student_name };
}

const showScheduleModal = ref(false);
const scheduleForm = useForm({ date: '', start_time: '' });

function openScheduleModal() {
    scheduleForm.reset();
    showScheduleModal.value = true;
}

function submitSchedule() {
    scheduleForm.post(route('appointments.slots.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
            scheduleForm.reset();
        },
    });
}

function deleteSlot(id: number) {
    router.delete(route('appointments.slots.destroy', id), {
        preserveScroll: true,
    });
}

const STATS = computed(() => [
    {
        label: 'Pending Requests',
        value: props.tabCounts.requests,
        accent: 'text-red-500',
        border: 'border-l-red-400 border-r-red-400',
    },
    {
        label: 'Scheduled',
        value: props.tabCounts.scheduled,
        accent: 'text-green-600',
        border: 'border-l-green-500',
    },
    {
        label: 'Session History',
        value: props.tabCounts.history,
        accent: 'text-amber-600',
        border: 'border-l-amber-400',
    },
    {
        label: 'Rejected',
        value: props.tabCounts.rejected,
        accent: 'text-orange-500',
        border: 'border-l-orange-400',
    },
    {
        label: 'Missed',
        value: props.tabCounts.missed,
        accent: 'text-purple-600',
        border: 'border-l-purple-400',
    },
]);
</script>

<template>
    <Head title="Appointments" />

    <AdminLayout title="Appointments">
        <!-- Stats Row -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div
                v-for="stat in STATS"
                :key="stat.label"
                :class="[
                    'rounded-xl border border-l-4 border-gray-100 bg-white px-5 py-4 shadow-sm',
                    stat.border,
                ]"
            >
                <p class="text-text-muted mb-2 text-xs font-medium">
                    {{ stat.label }}
                </p>
                <p :class="['text-3xl leading-none font-black', stat.accent]">
                    {{ stat.value }}
                </p>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="items-start gap-6 lg:flex">
            <!-- Left: Tabs + Cards Panel -->
            <div class="mb-6 flex h-[calc(100vh-16rem)] min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">

                <!-- Panel Header: Tabs -->
                <div class="shrink-0 border-b border-gray-100 px-4 py-3">
                    <div class="flex w-fit max-w-full gap-0.5 overflow-x-auto rounded-xl bg-gray-100 p-1">
                        <button
                            v-for="tab in TABS"
                            :key="tab.key"
                            type="button"
                            :class="[
                                'flex shrink-0 items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150',
                                activeTab === tab.key
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700',
                            ]"
                            @click="switchTab(tab.key)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Panel Body -->
                <!-- Empty State -->
                <div
                    v-if="appointments.length === 0"
                    class="flex flex-1 flex-col items-center justify-center py-20"
                >
                    <CalendarDaysIcon class="mb-3 h-10 w-10 text-gray-200" />
                    <p class="text-sm font-medium text-gray-400">
                        No appointments here
                    </p>
                    <p class="mt-1 text-xs text-gray-300">
                        This category is currently empty
                    </p>
                </div>

                <!-- Appointment Cards -->
                <div v-else class="flex-1 overflow-y-auto space-y-2 p-4">
                    <div
                        v-for="apt in appointments"
                        :key="apt.id"
                        class="group flex h-20 items-center gap-4 rounded-xl border border-gray-100 bg-gray-50 px-4 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm"
                    >
                        <!-- Avatar -->
                        <button
                            type="button"
                            @click="openProfile(apt)"
                            class="bg-sidebar/10 hover:bg-sidebar/20 flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors"
                        >
                            <span class="text-sidebar text-sm font-bold">{{
                                apt.student_profile.initials
                            }}</span>
                        </button>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <button
                                type="button"
                                @click="openProfile(apt)"
                                class="text-text-primary hover:text-sidebar block w-full truncate text-left text-sm font-semibold transition-colors"
                            >
                                {{ apt.student_name }}
                            </button>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    {{ apt.context }}
                                </span>
                                <span class="text-text-muted flex items-center gap-1 text-xs">
                                    <CalendarDaysIcon class="h-3 w-3 shrink-0 text-gray-300" />
                                    {{ formatDateTime(apt.date, apt.time) }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex shrink-0 items-center gap-2">
                            <template v-if="activeTab === 'requests'">
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-500 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                    @click="confirmAction('reject', apt)"
                                >
                                    Reject
                                </button>
                                <button
                                    type="button"
                                    class="bg-sidebar hover:bg-sidebar/90 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-colors"
                                    @click="confirmAction('approve', apt)"
                                >
                                    Approve
                                </button>
                            </template>
                            <template v-else-if="activeTab === 'scheduled'">
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                    Scheduled
                                </span>
                                <button
                                    type="button"
                                    class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-600 transition-colors hover:bg-amber-50"
                                    @click="complete(apt.id)"
                                >
                                    Mark Done
                                </button>
                            </template>
                            <template v-else-if="activeTab === 'missed'">
                                <span class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                    Missed
                                </span>
                            </template>
                            <template v-else>
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        STATUS_BADGE[apt.status] ?? 'bg-gray-100 text-gray-600',
                                    ]"
                                >
                                    {{ apt.status === 'Completed' ? 'Done' : apt.status }}
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Schedule Panel -->
            <div class="mb-6 flex h-[calc(100vh-16rem)] w-72 shrink-0 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <!-- Panel Header -->
                    <div class="border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-text-primary text-sm font-semibold">
                                Available Slots
                            </h3>
                            <span
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
                            >
                                {{ availableSlots.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Slot List -->
                    <div class="max-h-96 overflow-y-auto p-4">
                        <div
                            v-if="availableSlots.length === 0"
                            class="flex flex-col items-center py-8"
                        >
                            <ClockIcon class="mb-2 h-8 w-8 text-gray-200" />
                            <p class="text-xs text-gray-400">
                                No slots scheduled yet
                            </p>
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="slot in availableSlots"
                                :key="slot.id"
                                :class="[
                                    'group flex items-center justify-between rounded-lg p-3 transition-colors',
                                    slot.taken
                                        ? 'bg-red-50'
                                        : 'bg-gray-50 hover:bg-gray-100',
                                ]"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        :class="[
                                            'h-2 w-2 shrink-0 rounded-full',
                                            slot.taken
                                                ? 'bg-red-400'
                                                : 'bg-green-400',
                                        ]"
                                    />
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-gray-700"
                                        >
                                            {{ slot.date }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[11px] text-gray-400"
                                        >
                                            {{ slot.start_time }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span
                                        v-if="slot.taken"
                                        class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-500"
                                    >
                                        Taken
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        class="rounded-lg p-1.5 text-red-400 transition-colors hover:bg-red-100 hover:text-red-600"
                                        @click="deleteSlot(slot.id)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Footer -->
                    <div class="border-t border-gray-100 p-4">
                        <button
                            type="button"
                            class="bg-sidebar hover:bg-sidebar/90 w-full rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                            @click="openScheduleModal"
                        >
                            + Add Time Slot
                        </button>
                    </div>
                </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="pendingAction"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="absolute inset-0 bg-black/30 backdrop-blur-sm"
                        @click="pendingAction = null"
                    />

                    <div
                        class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
                    >
                        <div
                            :class="[
                                'mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full',
                                pendingAction.type === 'approve'
                                    ? 'bg-green-100'
                                    : 'bg-red-100',
                            ]"
                        >
                            <CheckCircleIcon
                                v-if="pendingAction.type === 'approve'"
                                class="h-6 w-6 text-green-600"
                            />
                            <ExclamationCircleIcon
                                v-else
                                class="h-6 w-6 text-red-500"
                            />
                        </div>
                        <h3
                            class="text-text-primary mb-1 text-center text-base font-bold"
                        >
                            {{
                                pendingAction.type === 'approve'
                                    ? 'Approve Appointment?'
                                    : 'Reject Appointment?'
                            }}
                        </h3>
                        <p class="text-text-muted mb-6 text-center text-sm">
                            {{
                                pendingAction.type === 'approve'
                                    ? `Confirm the appointment request from ${pendingAction.name}.`
                                    : `Reject the request from ${pendingAction.name}. This cannot be undone.`
                            }}
                        </p>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="border-border-light text-text-secondary flex-1 rounded-xl border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
                                @click="pendingAction = null"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors',
                                    pendingAction.type === 'approve'
                                        ? 'bg-sidebar hover:bg-sidebar/90'
                                        : 'bg-red-500 hover:bg-red-600',
                                ]"
                                @click="executePending"
                            >
                                {{
                                    pendingAction.type === 'approve'
                                        ? 'Approve'
                                        : 'Reject'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Student Profile Modal -->
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
                    v-if="selectedStudent"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="absolute inset-0 bg-black/30 backdrop-blur-sm"
                        @click="selectedStudent = null"
                    />

                    <div
                        class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl"
                    >
                        <button
                            type="button"
                            class="text-text-muted absolute top-4 right-4 z-10 rounded-full p-1.5 transition-colors hover:bg-gray-100"
                            @click="selectedStudent = null"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <div class="p-7">
                            <!-- Profile Header -->
                            <div class="mb-6 flex items-center gap-4">
                                <div
                                    class="bg-sidebar/10 flex h-16 w-16 shrink-0 items-center justify-center rounded-full"
                                >
                                    <span
                                        class="text-sidebar text-xl font-bold"
                                        >{{ selectedStudent.initials }}</span
                                    >
                                </div>
                                <div>
                                    <p
                                        class="text-text-primary text-lg font-bold"
                                    >
                                        {{ selectedStudent.name }}
                                    </p>
                                    <p class="text-text-muted mt-0.5 text-sm">
                                        {{ selectedStudent.student_id }} &bull;
                                        {{ selectedStudent.program }} &bull;
                                        {{ selectedStudent.year_level }}
                                    </p>
                                    <p class="text-text-muted text-sm">
                                        {{ selectedStudent.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Info Grid -->
                            <div
                                class="mb-6 grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4"
                            >
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Program
                                    </p>
                                    <p
                                        class="text-text-primary mt-0.5 text-sm font-semibold"
                                    >
                                        {{ selectedStudent.program }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Year Level
                                    </p>
                                    <p
                                        class="text-text-primary mt-0.5 text-sm font-semibold"
                                    >
                                        {{ selectedStudent.year_level }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Student ID
                                    </p>
                                    <p
                                        class="text-text-primary mt-0.5 text-sm font-semibold"
                                    >
                                        {{ selectedStudent.student_id }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Total Appointments
                                    </p>
                                    <p
                                        class="text-text-primary mt-0.5 text-sm font-semibold"
                                    >
                                        {{ selectedStudent.total_appointments }}
                                    </p>
                                </div>
                            </div>

                            <!-- Appointment History -->
                            <p
                                class="text-text-primary mb-3 text-sm font-semibold"
                            >
                                Appointment History
                            </p>
                            <div class="space-y-3">
                                <div
                                    v-for="(h, i) in selectedStudent.history"
                                    :key="i"
                                    class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-text-primary text-sm font-semibold"
                                        >
                                            {{ h.context }}
                                        </p>
                                        <p
                                            class="text-text-muted mt-0.5 text-xs"
                                        >
                                            {{ formatDateTime(h.date, h.time) }}
                                        </p>
                                        <p
                                            v-if="h.note"
                                            class="text-text-muted mt-1 text-xs italic"
                                        >
                                            "{{ h.note }}"
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                            STATUS_BADGE[h.status] ??
                                                'bg-gray-100 text-gray-600',
                                        ]"
                                    >
                                        {{
                                            h.status === 'Completed'
                                                ? 'Done'
                                                : h.status
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="selectedStudent.history.length === 0"
                                    class="text-text-muted py-4 text-center text-xs"
                                >
                                    No appointment history.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Schedule Modal -->
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
                    v-if="showScheduleModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div
                        class="absolute inset-0 bg-black/30 backdrop-blur-sm"
                        @click="showScheduleModal = false"
                    />

                    <div
                        class="relative w-full max-w-sm rounded-2xl bg-white p-7 shadow-xl"
                    >
                        <button
                            type="button"
                            class="text-text-muted absolute top-4 right-4 rounded-full p-1 transition-colors hover:bg-gray-100"
                            @click="showScheduleModal = false"
                        >
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <h2
                            class="text-text-primary mb-1 text-center text-base font-bold"
                        >
                            Add Available Slot
                        </h2>
                        <p class="mb-5 text-center text-xs text-gray-400">
                            GCU Operating Hours: 8:00 AM – 6:00 PM
                        </p>

                        <form
                            class="space-y-4"
                            @submit.prevent="submitSchedule"
                        >
                            <div>
                                <label class="text-text-secondary mb-1.5 block text-xs font-medium">Date</label>
                                <input
                                    v-model="scheduleForm.date"
                                    type="date"
                                    required
                                    :min="todayISO"
                                    class="border-border-light focus:ring-sidebar/30 focus:border-sidebar text-text-primary w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:outline-none"
                                />
                                <p v-if="scheduleForm.errors.date" class="mt-1 text-xs text-red-500">
                                    {{ scheduleForm.errors.date }}
                                </p>
                            </div>
                            <div>
                                <label class="text-text-secondary mb-2 block text-xs font-medium">
                                    Start Time
                                    <span v-if="scheduleForm.start_time" class="ml-2 font-semibold text-sidebar">
                                        · {{ TIME_SLOTS.find(s => s.value === scheduleForm.start_time)?.label }}
                                    </span>
                                </label>
                                <div class="grid grid-cols-4 gap-2">
                                    <button
                                        v-for="slot in TIME_SLOTS"
                                        :key="slot.value"
                                        type="button"
                                        :class="[
                                            'rounded-lg border py-2 text-xs font-medium transition-colors',
                                            scheduleForm.start_time === slot.value
                                                ? 'bg-sidebar border-sidebar text-white'
                                                : 'border-border-light bg-white text-text-secondary hover:border-sidebar/40 hover:bg-sidebar/5',
                                        ]"
                                        @click="scheduleForm.start_time = slot.value"
                                    >
                                        {{ slot.label }}
                                    </button>
                                </div>
                                <p v-if="scheduleForm.errors.start_time" class="mt-1 text-xs text-red-500">
                                    {{ scheduleForm.errors.start_time }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="button"
                                    class="border-border-light text-text-secondary flex-1 rounded-xl border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
                                    @click="showScheduleModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="scheduleForm.processing || !scheduleForm.start_time"
                                    class="bg-sidebar hover:bg-sidebar/90 flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60"
                                >
                                    {{ scheduleForm.processing ? 'Saving…' : 'Save Slot' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
