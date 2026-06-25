<script setup lang="ts">
import { usePollingReload } from '@/composables/usePolling';
import AddSlotModal from '@/Pages/Appointments/Modal/AddSlotModal.vue';
import CheckInQrModal from '@/Pages/Appointments/Modal/CheckInQrModal.vue';
import ConfirmActionModal from '@/Pages/Appointments/Modal/ConfirmActionModal.vue';
import StudentProfileModal from '@/Pages/Appointments/Modal/StudentProfileModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    Appointment,
    AppointmentFilters,
    AppointmentStudentProfile,
    AppointmentTab,
    AppointmentTabCounts,
    AvailableSlot,
    CheckInReadyAppointment,
} from '@/types';
import {
    CalendarDaysIcon,
    ClockIcon,
    QrCodeIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    appointments: Appointment[];
    tabCounts: AppointmentTabCounts;
    availableSlots: AvailableSlot[];
    checkInReady: CheckInReadyAppointment[];
    checkInDebug?: unknown; // TEMP DEBUG — remove with the badge below
    filters: AppointmentFilters;
}>();

const TABS: { key: AppointmentTab; label: string }[] = [
    { key: 'requests', label: 'Requests' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'missed', label: 'Missed' },
    { key: 'history', label: 'History' },
    { key: 'rejected', label: 'Rejected' },
];

usePollingReload(['appointments', 'tabCounts', 'availableSlots', 'checkInReady', 'checkInDebug'], { interval: 15_000 });

const activeTab = computed(() => props.filters.tab ?? 'requests');

function switchTab(tab: AppointmentTab) {
    router.get(route('appointments.index'), { tab }, { preserveState: true, replace: true });
}

const pendingAction = ref<{
    type: 'approve' | 'reject';
    id: number;
    name: string;
} | null>(null);

function approve(id: number) {
    router.patch(route('appointments.approve', id), {}, { preserveScroll: true });
}

function reject(id: number) {
    router.patch(route('appointments.reject', id), {}, { preserveScroll: true });
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

const qrAppointment = ref<Appointment | CheckInReadyAppointment | null>(null);
const qrDataUrl = ref<string>('');
const qrCheckedIn = ref(false);
// Tracks whether the open session has been seen in the check-in window, so its later
// disappearance can be read as a successful check-in rather than a never-ready test QR.
const qrWasReady = ref(false);

function loadSurfaced(): Set<number> {
    try {
        const raw = sessionStorage.getItem('surfaced_checkin_ids');
        return raw ? new Set(JSON.parse(raw) as number[]) : new Set();
    } catch {
        return new Set();
    }
}

function saveSurfaced(ids: Set<number>) {
    sessionStorage.setItem('surfaced_checkin_ids', JSON.stringify([...ids]));
}

const surfacedCheckInIds = ref<Set<number>>(loadSurfaced());

async function openCheckInQr(apt: Appointment) {
    if (!apt.can_check_in || !apt.checkin_url) return;
    surfacedCheckInIds.value.add(apt.id);
    saveSurfaced(surfacedCheckInIds.value);
    await renderCheckInQr(apt);
}

async function openCheckInQrTest(apt: Appointment) {
    if (!apt.checkin_url) return;
    await renderCheckInQr(apt);
}

async function renderCheckInQr(apt: Appointment | CheckInReadyAppointment) {
    if (!apt.checkin_url) return;
    qrAppointment.value = apt;
    qrDataUrl.value = await QRCode.toDataURL(apt.checkin_url, { width: 240, margin: 1 });
}

function closeCheckInQr() {
    qrAppointment.value = null;
    qrDataUrl.value = '';
    qrCheckedIn.value = false;
    qrWasReady.value = false;
}

watch(
    () => props.checkInReady,
    (checkInReady) => {
        if (qrAppointment.value) {
            const open = qrAppointment.value;
            const stillReady = checkInReady.some((apt) => apt.id === open.id);

            if (stillReady) {
                qrWasReady.value = true;
            } else if (qrWasReady.value && !qrCheckedIn.value) {
                const expiresAt = open.checkin_expires_at ? new Date(open.checkin_expires_at).getTime() : null;
                if (expiresAt !== null && Date.now() < expiresAt) {
                    qrCheckedIn.value = true;
                }
            }
            return;
        }

        const ready = checkInReady.find(
            (apt) => apt.checkin_url && !surfacedCheckInIds.value.has(apt.id),
        );

        if (!ready) return;

        surfacedCheckInIds.value.add(ready.id);
        saveSurfaced(surfacedCheckInIds.value);

        if (activeTab.value !== 'scheduled') {
            router.get(
                route('appointments.index'),
                { tab: 'scheduled' },
                { preserveState: true, replace: true },
            );
        }

        renderCheckInQr(ready);
    },
    { immediate: true, deep: true },
);

// ─────────────────────────────────────────────────────────────
// Student Profile
// ─────────────────────────────────────────────────────────────

const selectedStudent = ref<(AppointmentStudentProfile & { name: string }) | null>(null);

function openProfile(apt: Appointment) {
    selectedStudent.value = { ...apt.student_profile, name: apt.student_name };
}

// ─────────────────────────────────────────────────────────────
// Schedule Slots
// ─────────────────────────────────────────────────────────────

const showScheduleModal = ref(false);

function deleteSlot(id: number) {
    router.delete(route('appointments.slots.destroy', id), { preserveScroll: true });
}

// ─────────────────────────────────────────────────────────────
// UI Helpers
// ─────────────────────────────────────────────────────────────

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
        <!-- TEMP DEBUG — remove once QR popup is confirmed -->
        <pre v-if="checkInDebug" class="mb-4 overflow-auto rounded-lg bg-gray-900 p-3 text-xs text-green-300">checkInReady={{ checkInReady.length }}
{{ JSON.stringify(checkInDebug, null, 2) }}</pre>

        <!-- Stats Row -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div v-for="stat in STATS" :key="stat.label" :class="[
                'rounded-xl border border-l-4 border-gray-100 bg-white px-5 py-4 shadow-sm',
                stat.border,
            ]">
                <p class="text-text-muted mb-2 text-xs font-medium">{{ stat.label }}</p>
                <p :class="['text-3xl leading-none font-black', stat.accent]">{{ stat.value }}</p>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="items-start gap-6 lg:flex">
            <!-- Left: Tabs + Cards Panel -->
            <div
                class="mb-6 flex h-[calc(100vh-16rem)] min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <!-- Panel Header: Tabs -->
                <div class="shrink-0 border-b border-gray-100 px-4 py-3">
                    <div class="flex w-fit max-w-full gap-0.5 overflow-x-auto rounded-xl bg-gray-100 p-1">
                        <button v-for="tab in TABS" :key="tab.key" type="button" :class="[
                            'flex shrink-0 items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150',
                            activeTab === tab.key
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700',
                        ]" @click="switchTab(tab.key)">
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Panel Body -->
                <div v-if="appointments.length === 0" class="flex flex-1 flex-col items-center justify-center py-20">
                    <CalendarDaysIcon class="mb-3 h-10 w-10 text-gray-200" />
                    <p class="text-sm font-medium text-gray-400">No appointments here</p>
                    <p class="mt-1 text-xs text-gray-300">This category is currently empty</p>
                </div>

                <div v-else class="flex-1 overflow-y-auto space-y-2 p-4">
                    <div v-for="apt in appointments" :key="apt.id"
                        class="group flex h-20 items-center gap-4 rounded-xl border border-gray-100 bg-gray-50 px-4 transition-all duration-200 hover:border-gray-200 hover:bg-white hover:shadow-sm">
                        <!-- Avatar -->
                        <button type="button"
                            class="bg-sidebar/10 hover:bg-sidebar/20 flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors"
                            @click="openProfile(apt)">
                            <span class="text-sidebar text-sm font-bold">{{ apt.student_profile.initials }}</span>
                        </button>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <button type="button"
                                class="text-text-primary hover:text-sidebar block w-full truncate text-left text-sm font-semibold transition-colors"
                                @click="openProfile(apt)">
                                {{ apt.student_name }}
                            </button>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
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
                                <button type="button"
                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-500 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                    @click="confirmAction('reject', apt)">
                                    Reject
                                </button>
                                <button type="button"
                                    class="bg-sidebar hover:bg-sidebar/90 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-colors"
                                    @click="confirmAction('approve', apt)">
                                    Approve
                                </button>
                            </template>
                            <template v-else-if="activeTab === 'scheduled'">
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                    Scheduled
                                </span>
                                <button type="button" :disabled="!apt.can_check_in"
                                    :title="apt.can_check_in ? 'Show the check-in QR for this session' : `Available at ${apt.time}`"
                                    class="flex items-center gap-1.5 rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-600 transition-colors hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:hover:bg-transparent"
                                    @click="openCheckInQr(apt)">
                                    <QrCodeIcon class="h-4 w-4" />
                                    Check-in QR
                                </button>
                                <button type="button" title="Test: open the QR popup ignoring the time window"
                                    class="flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-500 transition-colors hover:bg-gray-50"
                                    @click="openCheckInQrTest(apt)">
                                    <QrCodeIcon class="h-4 w-4" />
                                    Test QR
                                </button>
                            </template>
                            <template v-else-if="activeTab === 'missed'">
                                <span
                                    class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                    Missed
                                </span>
                            </template>
                            <template v-else>
                                <span :class="[
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    STATUS_BADGE[apt.status] ?? 'bg-gray-100 text-gray-600',
                                ]">
                                    {{ apt.status === 'Completed' ? 'Done' : apt.status }}
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Schedule Panel -->
            <div
                class="mb-6 flex h-[calc(100vh-16rem)] w-72 shrink-0 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-text-primary text-sm font-semibold">Available Slots</h3>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">
                            {{ availableSlots.length }}
                        </span>
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto p-4">
                    <div v-if="availableSlots.length === 0" class="flex flex-col items-center py-8">
                        <ClockIcon class="mb-2 h-8 w-8 text-gray-200" />
                        <p class="text-xs text-gray-400">No slots scheduled yet</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div v-for="slot in availableSlots" :key="slot.id" :class="[
                            'group flex items-center justify-between rounded-lg p-3 transition-colors',
                            slot.taken ? 'bg-red-50' : 'bg-gray-50 hover:bg-gray-100',
                        ]">
                            <div class="flex items-center gap-2.5">
                                <div :class="[
                                    'h-2 w-2 shrink-0 rounded-full',
                                    slot.taken ? 'bg-red-400' : 'bg-green-400',
                                ]" />
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">{{ slot.date }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">{{ slot.start_time }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <span v-if="slot.taken"
                                    class="rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-500">
                                    Taken
                                </span>
                                <button v-else type="button"
                                    class="rounded-lg p-1.5 text-red-400 transition-colors hover:bg-red-100 hover:text-red-600"
                                    @click="deleteSlot(slot.id)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 p-4">
                    <button type="button"
                        class="bg-sidebar hover:bg-sidebar/90 w-full rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        @click="showScheduleModal = true">
                        + Add Time Slot
                    </button>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <ConfirmActionModal v-if="pendingAction" :type="pendingAction.type" :name="pendingAction.name"
            @confirm="executePending" @cancel="pendingAction = null" />

        <CheckInQrModal v-if="qrAppointment" :appointment="qrAppointment" :qr-data-url="qrDataUrl"
            :checked-in="qrCheckedIn" @close="closeCheckInQr" />

        <StudentProfileModal v-if="selectedStudent" :student="selectedStudent" :status-badge="STATUS_BADGE"
            @close="selectedStudent = null" />

        <AddSlotModal v-if="showScheduleModal" @close="showScheduleModal = false" @saved="showScheduleModal = false" />
    </AdminLayout>
</template>