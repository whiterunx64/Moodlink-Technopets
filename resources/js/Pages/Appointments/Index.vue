<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    Appointment,
    AppointmentFilters,
    AppointmentStudentProfile,
    AppointmentTab,
    AppointmentTabCounts,
    AvailableSlot,
} from '@/types';
import { TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    appointments: Appointment[];
    tabCounts: AppointmentTabCounts;
    availableSlots: AvailableSlot[];
    filters: AppointmentFilters;
}>();

// ── Tabs ──────────────────────────────────────────────────────────────────────

const TABS: { key: AppointmentTab; label: string; badge: string }[] = [
    { key: 'requests', label: 'Requests', badge: 'bg-red-500' },
    { key: 'scheduled', label: 'Scheduled', badge: 'bg-green-500' },
    { key: 'history', label: 'History', badge: 'bg-gray-400' },
    { key: 'rejected', label: 'Rejected', badge: 'bg-orange-400' },
];

const activeTab = computed(() => props.filters.tab ?? 'requests');

function switchTab(tab: AppointmentTab) {
    router.get(
        route('appointments.index'),
        { tab },
        { preserveState: true, replace: true },
    );
}

// ── Appointment actions ───────────────────────────────────────────────────────

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

// ── Status badge map ──────────────────────────────────────────────────────────

const STATUS_BADGE: Record<string, string> = {
    Scheduled: 'bg-green-100 text-green-700',
    Completed: 'bg-amber-100 text-amber-700',
    Rejected: 'bg-red-100 text-red-600',
    Pending: 'bg-amber-50 text-amber-600',
};

// ── Student profile modal ─────────────────────────────────────────────────────

const selectedStudent = ref<
    (AppointmentStudentProfile & { name: string }) | null
>(null);

function openProfile(apt: Appointment) {
    selectedStudent.value = { ...apt.student_profile, name: apt.student_name };
}

// ── Set Schedule modal ────────────────────────────────────────────────────────

const showScheduleModal = ref(false);

const scheduleForm = useForm({ date: '', start_time: '' });

function openScheduleModal() {
    scheduleForm.reset();
    showScheduleModal.value = true;
}

function submitSchedule() {
    scheduleForm.post(route('appointments.schedules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
            scheduleForm.reset();
        },
    });
}

function deleteSlot(id: number) {
    router.delete(route('appointments.schedules.destroy', id), {
        preserveScroll: true,
    });
}
</script>

<template>

    <Head title="Appointments" />

    <AdminLayout title="Appointments">
        <div class="items-start gap-6 lg:flex">
            <!-- ── Left column ────────────────────────────────────────────── -->
            <div class="mb-6 min-w-0 flex-1">
                <!-- Tabs -->
                <div class="border-border-light mb-5 flex items-center gap-1 overflow-scroll border-b">
                    <button v-for="tab in TABS" :key="tab.key" type="button" :class="[
                        '-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                        activeTab === tab.key
                            ? 'border-sidebar text-sidebar'
                            : 'text-text-muted hover:text-text-secondary border-transparent',
                    ]" @click="switchTab(tab.key)">
                        {{ tab.label }}
                        <span :class="[
                            'inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold text-white',
                            tab.badge,
                        ]">
                            {{ tabCounts[tab.key] }}
                        </span>
                    </button>
                </div>

                <!-- Appointment list -->
                <div class="border-border-light divide-border-light divide-y rounded-2xl border bg-white shadow-sm">
                    <div v-if="appointments.length === 0" class="text-text-muted py-16 text-center text-sm">
                        No appointments in this category.
                    </div>

                    <div v-for="apt in appointments" :key="apt.id" class="px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <!-- Info -->
                            <div class="min-w-0">
                                <button type="button"
                                    class="text-sidebar cursor-pointer text-left text-sm font-semibold capitalize hover:underline"
                                    @click="openProfile(apt)">
                                    {{ apt.student_name }}
                                </button>
                                <p class="text-text-primary mt-0.5 text-sm">
                                    {{ apt.context }}
                                </p>
                                <p v-if="apt.note" class="text-text-muted mt-1 text-sm italic">
                                    "{{ apt.note }}"
                                </p>
                                <p class="text-text-muted mt-2">
                                    {{
                                        new Date(
                                            apt.date + ' ' + apt.time,
                                        ).toLocaleString('en-US', {
                                            weekday: 'short',
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            hour12: true,
                                        })
                                    }}
                                </p>
                            </div>

                            <!-- Actions / badge -->
                            <div class="mt-0.5 flex flex-col gap-2">
                                <template v-if="activeTab === 'requests'">
                                    <button type="button"
                                        class="rounded-lg border border-green-400 px-4 py-1.5 text-xs font-semibold text-green-600 transition-colors hover:bg-green-50"
                                        @click="approve(apt.id)">
                                        Approve
                                    </button>
                                    <button type="button"
                                        class="rounded-lg border border-red-300 px-4 py-1.5 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50"
                                        @click="reject(apt.id)">
                                        Reject
                                    </button>
                                </template>

                                <template v-else-if="activeTab === 'scheduled'">
                                    <span
                                        class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Scheduled
                                    </span>
                                    <button type="button"
                                        class="rounded-lg border border-amber-400 px-4 py-1.5 text-xs font-semibold text-amber-600 transition-colors hover:bg-amber-50"
                                        @click="complete(apt.id)">
                                        Mark Done
                                    </button>
                                </template>

                                <template v-else>
                                    <span :class="[
                                        'rounded-full px-3 py-1 text-xs font-semibold',
                                        STATUS_BADGE[apt.status] ??
                                        'bg-gray-100 text-gray-600',
                                    ]">
                                        {{
                                            apt.status === 'Completed'
                                                ? 'Done'
                                                : apt.status
                                        }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Right column: available slots ─────────────────────────── -->
            <div class="w-72 shrink-0">
                <div class="border-border-light sticky top-6 rounded-2xl border bg-white p-6 shadow-sm">
                    <h3 class="text-text-primary mb-4 text-sm font-semibold">
                        Available Slots
                    </h3>

                    <div class="mb-6 space-y-3">
                        <div v-if="availableSlots.length === 0" class="text-text-muted py-4 text-center text-xs">
                            No available slots set.
                        </div>

                        <div v-for="slot in availableSlots" :key="slot.id"
                            class="group flex items-center justify-between">
                            <div>
                                <p class="text-text-primary text-xs font-medium">
                                    {{ slot.date }}
                                    <span v-if="slot.taken" class="ml-1 font-semibold text-red-500">Taken</span>
                                </p>
                                <p class="text-text-muted mt-0.5 text-xs">
                                    {{ slot.start_time }}
                                </p>
                            </div>
                            <button v-if="!slot.taken" type="button"
                                class="rounded p-1 text-red-400 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-50"
                                @click="deleteSlot(slot.id)">
                                <TrashIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <button type="button"
                        class="bg-sidebar hover:bg-sidebar/90 w-full rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        @click="openScheduleModal">
                        Set Schedule
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Student Profile Modal ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="selectedStudent" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="selectedStudent = null" />

                    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
                        <!-- Close -->
                        <button type="button"
                            class="text-text-muted absolute top-4 right-4 z-10 rounded-full p-1.5 transition-colors hover:bg-gray-100"
                            @click="selectedStudent = null">
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <div class="p-7">
                            <!-- Avatar + identity -->
                            <div class="mb-6 flex items-center gap-4">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100">
                                    <span class="text-xl font-bold text-blue-500">{{ selectedStudent.initials }}</span>
                                </div>
                                <div>
                                    <p class="text-text-primary text-lg font-bold">
                                        {{ selectedStudent.name }}
                                    </p>
                                    <p class="text-text-muted mt-0.5 text-sm">
                                        {{ selectedStudent.student_id }} &bull;
                                        {{ selectedStudent.section }} &bull;
                                        {{ selectedStudent.year_level }}
                                    </p>
                                    <p class="text-text-muted text-sm">
                                        {{ selectedStudent.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Stats grid -->
                            <div class="mb-6 grid grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Section
                                    </p>
                                    <p class="text-text-primary mt-0.5 text-sm font-semibold">
                                        {{ selectedStudent.section }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Year Level
                                    </p>
                                    <p class="text-text-primary mt-0.5 text-sm font-semibold">
                                        {{ selectedStudent.year_level }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Student ID
                                    </p>
                                    <p class="text-text-primary mt-0.5 text-sm font-semibold">
                                        {{ selectedStudent.student_id }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-text-muted text-xs">
                                        Total Appointments
                                    </p>
                                    <p class="text-text-primary mt-0.5 text-sm font-semibold">
                                        {{ selectedStudent.total_appointments }}
                                    </p>
                                </div>
                            </div>

                            <!-- Appointment history -->
                            <p class="text-text-primary mb-3 text-sm font-semibold">
                                Appointment History
                            </p>

                            <div class="space-y-4">
                                <div v-for="(h, i) in selectedStudent.history" :key="i"
                                    class="flex items-start justify-between gap-4 rounded-xl bg-gray-50 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-text-primary text-sm font-semibold">
                                            {{ h.context }}
                                        </p>
                                        <p class="text-text-muted mt-0.5">
                                            {{
                                                new Date(
                                                    h.date + ' ' + h.time,
                                                ).toLocaleString('en-US', {
                                                    weekday: 'short',
                                                    month: 'short',
                                                    day: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                    hour12: true,
                                                })
                                            }}
                                        </p>
                                        <p v-if="h.note" class="text-text-muted mt-1 text-xs italic">
                                            "{{ h.note }}"
                                        </p>
                                    </div>
                                    <span :class="[
                                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                        STATUS_BADGE[h.status] ??
                                        'bg-gray-100 text-gray-600',
                                    ]">
                                        {{
                                            h.status === 'Completed'
                                                ? 'Done'
                                                : h.status
                                        }}
                                    </span>
                                </div>

                                <p v-if="selectedStudent.history.length === 0"
                                    class="text-text-muted py-4 text-center text-xs">
                                    No appointment history.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Set Schedule Modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="showScheduleModal = false" />

                    <div class="relative w-full max-w-sm rounded-2xl bg-white p-7 shadow-xl">
                        <button type="button"
                            class="text-text-muted absolute top-4 right-4 rounded-full p-1 transition-colors hover:bg-gray-100"
                            @click="showScheduleModal = false">
                            <XMarkIcon class="h-4 w-4" />
                        </button>

                        <h2 class="text-text-primary mb-6 text-center text-base font-bold">
                            Set Available Schedule
                        </h2>

                        <form @submit.prevent="submitSchedule" class="space-y-4">
                            <div>
                                <label class="text-text-secondary mb-1.5 block text-xs font-medium">Date</label>
                                <input v-model="scheduleForm.date" type="date" required
                                    class="border-border-light text-text-primary focus:ring-sidebar/30 focus:border-sidebar w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:outline-none" />
                                <p v-if="scheduleForm.errors.date" class="mt-1 text-xs text-red-500">
                                    {{ scheduleForm.errors.date }}
                                </p>
                            </div>

                            <div>
                                <label class="text-text-secondary mb-1.5 block text-xs font-medium">Start time</label>
                                <input v-model="scheduleForm.start_time" type="time" required
                                    class="border-border-light text-text-primary focus:ring-sidebar/30 focus:border-sidebar w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:outline-none" />
                                <p v-if="scheduleForm.errors.start_time" class="mt-1 text-xs text-red-500">
                                    {{ scheduleForm.errors.start_time }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="button"
                                    class="border-border-light text-text-secondary flex-1 rounded-xl border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
                                    @click="showScheduleModal = false">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="scheduleForm.processing"
                                    class="bg-sidebar hover:bg-sidebar/90 flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60">
                                    {{
                                        scheduleForm.processing
                                            ? 'Saving…'
                                            : 'Done'
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
