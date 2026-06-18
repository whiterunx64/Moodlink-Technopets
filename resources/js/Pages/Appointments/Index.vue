<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    Appointment, AppointmentStudentProfile, AvailableSlot,
    AppointmentTabCounts, AppointmentFilters, AppointmentTab,
} from '@/types';

const props = defineProps<{
    appointments: Appointment[];
    tabCounts: AppointmentTabCounts;
    availableSlots: AvailableSlot[];
    filters: AppointmentFilters;
}>();

// ── Tabs ──────────────────────────────────────────────────────────────────────

const TABS: { key: AppointmentTab; label: string; badge: string }[] = [
    { key: 'requests',  label: 'Requests',  badge: 'bg-red-500' },
    { key: 'scheduled', label: 'Scheduled', badge: 'bg-green-500' },
    { key: 'history',   label: 'History',   badge: 'bg-gray-400' },
    { key: 'rejected',  label: 'Rejected',  badge: 'bg-orange-400' },
];

const activeTab = computed(() => props.filters.tab ?? 'requests');

function switchTab(tab: AppointmentTab) {
    router.get(route('appointments.index'), { tab }, { preserveState: true, replace: true });
}

// ── Appointment actions ───────────────────────────────────────────────────────

function approve(id: number) {
    router.patch(route('appointments.approve', id), {}, { preserveScroll: true });
}
function deny(id: number) {
    router.patch(route('appointments.deny', id), {}, { preserveScroll: true });
}
function complete(id: number) {
    router.patch(route('appointments.complete', id), {}, { preserveScroll: true });
}

// ── Status badge map ──────────────────────────────────────────────────────────

const STATUS_BADGE: Record<string, string> = {
    Scheduled: 'bg-green-100 text-green-700',
    Completed: 'bg-amber-100 text-amber-700',
    Rejected:  'bg-red-100 text-red-600',
    Pending:   'bg-amber-50 text-amber-600',
};

// ── Student profile modal ─────────────────────────────────────────────────────

const selectedStudent = ref<(AppointmentStudentProfile & { name: string }) | null>(null);

function openProfile(apt: Appointment) {
    selectedStudent.value = { ...apt.studentProfile, name: apt.studentName };
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
    router.delete(route('appointments.schedules.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Appointments" />

    <AdminLayout title="Appointments">
        <div class="flex gap-6 items-start">

            <!-- ── Left column ────────────────────────────────────────────── -->
            <div class="flex-1 min-w-0">

                <!-- Tabs -->
                <div class="flex items-center gap-1 mb-5 border-b border-border-light">
                    <button
                        v-for="tab in TABS"
                        :key="tab.key"
                        type="button"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
                            activeTab === tab.key
                                ? 'border-sidebar text-sidebar'
                                : 'border-transparent text-text-muted hover:text-text-secondary',
                        ]"
                        @click="switchTab(tab.key)"
                    >
                        {{ tab.label }}
                        <span :class="['inline-flex items-center justify-center w-5 h-5 rounded-full text-white text-xs font-bold', tab.badge]">
                            {{ tabCounts[tab.key] }}
                        </span>
                    </button>
                </div>

                <!-- Appointment list -->
                <div class="bg-white rounded-2xl border border-border-light shadow-sm divide-y divide-border-light">

                    <div v-if="appointments.length === 0" class="py-16 text-center text-sm text-text-muted">
                        No appointments in this category.
                    </div>

                    <div v-for="apt in appointments" :key="apt.id" class="px-6 py-5">
                        <div class="flex items-start justify-between gap-4">

                            <!-- Info -->
                            <div class="min-w-0">
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-sidebar hover:underline text-left"
                                    @click="openProfile(apt)"
                                >
                                    {{ apt.studentName }}
                                </button>
                                <p class="text-sm text-text-primary mt-0.5">{{ apt.context }}</p>
                                <p v-if="apt.note" class="text-sm text-text-muted italic mt-1">"{{ apt.note }}"</p>
                                <p class="text-xs text-text-muted mt-2">
                                    {{ apt.date }}&nbsp;&nbsp;{{ apt.time }}
                                </p>
                            </div>

                            <!-- Actions / badge -->
                            <div class="shrink-0 flex items-center gap-2 mt-0.5">
                                <template v-if="activeTab === 'requests'">
                                    <button type="button"
                                        class="px-4 py-1.5 rounded-lg border border-green-400 text-green-600 text-xs font-semibold hover:bg-green-50 transition-colors"
                                        @click="approve(apt.id)">
                                        Approve
                                    </button>
                                    <button type="button"
                                        class="px-4 py-1.5 rounded-lg border border-red-300 text-red-500 text-xs font-semibold hover:bg-red-50 transition-colors"
                                        @click="deny(apt.id)">
                                        Deny
                                    </button>
                                </template>

                                <template v-else-if="activeTab === 'scheduled'">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        Scheduled
                                    </span>
                                    <button type="button"
                                        class="px-4 py-1.5 rounded-lg border border-amber-400 text-amber-600 text-xs font-semibold hover:bg-amber-50 transition-colors"
                                        @click="complete(apt.id)">
                                        Mark Done
                                    </button>
                                </template>

                                <template v-else>
                                    <span :class="['px-3 py-1 rounded-full text-xs font-semibold', STATUS_BADGE[apt.status] ?? 'bg-gray-100 text-gray-600']">
                                        {{ apt.status === 'Completed' ? 'Done' : apt.status }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Right column: available slots ─────────────────────────── -->
            <div class="w-72 shrink-0">
                <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6 sticky top-6">
                    <h3 class="text-sm font-semibold text-text-primary mb-4">Available Slots</h3>

                    <div class="space-y-3 mb-6">
                        <div v-if="availableSlots.length === 0" class="text-xs text-text-muted text-center py-4">
                            No available slots set.
                        </div>

                        <div v-for="slot in availableSlots" :key="slot.id"
                            class="flex items-center justify-between group">
                            <div>
                                <p class="text-xs font-medium text-text-primary">
                                    {{ slot.date }}
                                    <span v-if="slot.taken" class="ml-1 font-semibold text-red-500">Taken</span>
                                </p>
                                <p class="text-xs text-text-muted mt-0.5">{{ slot.startTime }}</p>
                            </div>
                            <button v-if="!slot.taken" type="button"
                                class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded text-red-400 hover:bg-red-50"
                                @click="deleteSlot(slot.id)">
                                <TrashIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <button type="button"
                        class="w-full py-2.5 rounded-xl bg-sidebar text-white text-sm font-semibold hover:bg-sidebar/90 transition-colors"
                        @click="openScheduleModal">
                        Set Schedule
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Student Profile Modal ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedStudent" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="selectedStudent = null" />

                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

                        <!-- Close -->
                        <button type="button"
                            class="absolute top-4 right-4 p-1.5 rounded-full text-text-muted hover:bg-gray-100 transition-colors z-10"
                            @click="selectedStudent = null">
                            <XMarkIcon class="w-4 h-4" />
                        </button>

                        <div class="p-7">
                            <!-- Avatar + identity -->
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                    <span class="text-xl font-bold text-blue-500">{{ selectedStudent.initials }}</span>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-text-primary">{{ selectedStudent.name }}</p>
                                    <p class="text-sm text-text-muted mt-0.5">
                                        {{ selectedStudent.studentId }} &bull; {{ selectedStudent.yearLevel }}
                                    </p>
                                    <p class="text-sm text-text-muted">{{ selectedStudent.email }}</p>
                                </div>
                            </div>

                            <!-- Stats grid -->
                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                                <div>
                                    <p class="text-xs text-text-muted">Year Level</p>
                                    <p class="text-sm font-semibold text-text-primary mt-0.5">{{ selectedStudent.yearLevel }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted">Student ID</p>
                                    <p class="text-sm font-semibold text-text-primary mt-0.5">{{ selectedStudent.studentId }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-text-muted">Total Appointments</p>
                                    <p class="text-sm font-semibold text-text-primary mt-0.5">{{ selectedStudent.totalAppointments }}</p>
                                </div>
                            </div>

                            <!-- Appointment history -->
                            <p class="text-sm font-semibold text-text-primary mb-3">Appointment History</p>

                            <div class="space-y-4">
                                <div v-for="(h, i) in selectedStudent.history" :key="i"
                                    class="flex items-start justify-between gap-4 rounded-xl bg-gray-50 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-text-primary">{{ h.context }}</p>
                                        <p class="text-xs text-text-muted mt-0.5">{{ h.date }} &bull; {{ h.time }}</p>
                                        <p v-if="h.note" class="text-xs text-text-muted italic mt-1">"{{ h.note }}"</p>
                                    </div>
                                    <span :class="['shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold', STATUS_BADGE[h.status] ?? 'bg-gray-100 text-gray-600']">
                                        {{ h.status === 'Completed' ? 'Done' : h.status }}
                                    </span>
                                </div>

                                <p v-if="selectedStudent.history.length === 0" class="text-xs text-text-muted text-center py-4">
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
            <Transition
                enter-active-class="transition duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30" @click="showScheduleModal = false" />

                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-7">
                        <button type="button"
                            class="absolute top-4 right-4 p-1 rounded-full text-text-muted hover:bg-gray-100 transition-colors"
                            @click="showScheduleModal = false">
                            <XMarkIcon class="w-4 h-4" />
                        </button>

                        <h2 class="text-base font-bold text-text-primary mb-6 text-center">Set Available Schedule</h2>

                        <form @submit.prevent="submitSchedule" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-text-secondary mb-1.5">Date</label>
                                <input v-model="scheduleForm.date" type="date" required
                                    class="w-full border border-border-light rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar/30 focus:border-sidebar" />
                                <p v-if="scheduleForm.errors.date" class="text-xs text-red-500 mt-1">{{ scheduleForm.errors.date }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-text-secondary mb-1.5">Start time</label>
                                <input v-model="scheduleForm.start_time" type="time" required
                                    class="w-full border border-border-light rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-sidebar/30 focus:border-sidebar" />
                                <p v-if="scheduleForm.errors.start_time" class="text-xs text-red-500 mt-1">{{ scheduleForm.errors.start_time }}</p>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="button"
                                    class="flex-1 py-2.5 rounded-xl border border-border-light text-sm font-medium text-text-secondary hover:bg-gray-50 transition-colors"
                                    @click="showScheduleModal = false">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="scheduleForm.processing"
                                    class="flex-1 py-2.5 rounded-xl bg-sidebar text-white text-sm font-semibold hover:bg-sidebar/90 transition-colors disabled:opacity-60">
                                    {{ scheduleForm.processing ? 'Saving…' : 'Done' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
