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
    CheckInReadyAppointment,
} from '@/types';
import {
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    QrCodeIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import type { FunctionalComponent } from 'vue';
import { computed, defineAsyncComponent, ref, watch } from 'vue';

const AddSlotModal = defineAsyncComponent(
    () => import('@/Pages/Appointments/Modal/AddSlotModal.vue'),
);
const CheckInQrModal = defineAsyncComponent(
    () => import('@/Pages/Appointments/Modal/CheckInQrModal.vue'),
);
const StudentProfileModal = defineAsyncComponent(
    () => import('@/Pages/Appointments/Modal/StudentProfileModal.vue'),
);

const props = defineProps<{
    appointments: Appointment[];
    tabCounts: AppointmentTabCounts;
    availableSlots: AvailableSlot[];
    checkInReady: CheckInReadyAppointment[];
    checkInDebug?: unknown; // TEMP DEBUG — remove with the badge below
    filters: AppointmentFilters;
    studentProfile?: AppointmentStudentProfile | null;
}>();

usePollingReload(
    [
        'appointments',
        'tabCounts',
        'availableSlots',
        'checkInReady',
        'checkInDebug',
    ],
    { interval: 15_000 },
);

const activeTab = computed(() => props.filters.tab ?? 'scheduled');

function switchTab(tab: AppointmentTab) {
    router.get(
        route('appointments.index'),
        { tab },
        { preserveState: true, replace: true },
    );
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
    qrDataUrl.value = await QRCode.toDataURL(apt.checkin_url, {
        width: 240,
        margin: 1,
    });
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
                const expiresAt = open.checkin_expires_at
                    ? new Date(open.checkin_expires_at).getTime()
                    : null;
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

const profileOpen = ref(false);
const profileLoading = ref(false);

function openProfile(apt: Appointment) {
    profileOpen.value = true;
    profileLoading.value = true;

    router.reload({
        only: ['studentProfile'],
        data: { student: apt.student_id },
        onFinish: () => {
            profileLoading.value = false;
        },
    });
}

function closeProfile() {
    profileOpen.value = false;
    profileLoading.value = false;
}

// ─────────────────────────────────────────────────────────────
// Schedule Slots
// ─────────────────────────────────────────────────────────────

const showScheduleModal = ref(false);

function deleteSlot(id: number) {
    router.delete(route('appointments.slots.destroy', id), {
        preserveScroll: true,
    });
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

const STATS = computed<
    {
        tab: AppointmentTab;
        label: string;
        value: number;
        accent: string;
        border: string;
        bg: string;
        icon: FunctionalComponent;
        iconBg: string;
    }[]
>(() => [
    {
        tab: 'scheduled',
        label: 'Scheduled',
        value: props.tabCounts.scheduled ?? 0,
        accent: 'text-green-600',
        border: 'border-l-green-500',
        bg: 'bg-green-50',
        icon: CheckCircleIcon,
        iconBg: 'bg-green-100 text-green-600',
    },
    {
        tab: 'history',
        label: 'Session History',
        value: props.tabCounts.history ?? 0,
        accent: 'text-amber-600',
        border: 'border-l-amber-400',
        bg: 'bg-amber-50',
        icon: ClockIcon,
        iconBg: 'bg-amber-100 text-amber-600',
    },
    {
        tab: 'missed',
        label: 'Missed',
        value: props.tabCounts.missed ?? 0,
        accent: 'text-purple-600',
        border: 'border-l-purple-400',
        bg: 'bg-purple-50',
        icon: ExclamationTriangleIcon,
        iconBg: 'bg-purple-100 text-purple-600',
    },
]);
</script>

<template>
    <Head title="Appointments" />

    <AdminLayout title="Appointments">
        <!-- TEMP DEBUG — remove once QR popup is confirmed -->
        <pre
            v-if="checkInDebug"
            class="mb-4 overflow-auto bg-gray-900 p-3 text-xs text-green-300"
        >
checkInReady={{ checkInReady.length }}
{{ JSON.stringify(checkInDebug, null, 2) }}</pre
        >

        <!-- Main Layout -->
        <div class="items-start gap-6 md:flex">
            <!-- Left: floating tabs + cards panel -->
            <div
                class="mb-6 flex h-[calc(100dvh-13rem)] min-h-112 min-w-0 flex-1 flex-col"
            >
                <!-- Floating tabs riding the top edge of the panel.
                     On small screens they wrap into a compact grid; from md they sit in a row. -->
                <div
                    role="tablist"
                    aria-label="Appointment categories"
                    class="grid grid-cols-2 gap-1.5 overflow-scroll sm:grid-cols-3 md:flex md:gap-1"
                >
                    <button
                        v-for="stat in STATS"
                        :key="stat.tab"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === stat.tab"
                        :class="[
                            'group/tab focus-visible:ring-sidebar relative flex flex-1 cursor-pointer items-center gap-3 px-4 py-3.5 text-left transition-all duration-150 focus-visible:ring-2 focus-visible:outline-none',
                            activeTab === stat.tab
                                ? 'z-10 bg-white shadow-[0_-1px_10px_rgba(15,23,42,0.06)] ring-1 ring-gray-100 md:-mb-px md:ring-0'
                                : [
                                      stat.bg,
                                      'ring-1 ring-black/5 hover:-translate-y-0.5 hover:shadow-sm',
                                  ],
                        ]"
                        @click="switchTab(stat.tab)"
                    >
                        <!-- Active accent bar along the bottom edge that meets the panel -->
                        <span
                            v-if="activeTab === stat.tab"
                            :class="[
                                'md:bxlock absolute inset-x-3 -bottom-px hidden h-0.5',
                                stat.accent.replace('text-', 'bg-'),
                            ]"
                        />
                        <span
                            :class="[
                                'flex h-11 w-11 shrink-0 items-center justify-center transition-colors',
                                stat.iconBg,
                            ]"
                        >
                            <component :is="stat.icon" class="h-5 w-5" />
                        </span>
                        <span class="flex min-w-0 flex-col">
                            <span
                                :class="[
                                    'text-lg leading-none font-extrabold tabular-nums',
                                    stat.accent,
                                ]"
                            >
                                {{ String(stat.value).padStart(2, '0') }}
                            </span>
                            <span
                                class="mt-1 truncate text-xs font-semibold text-gray-600"
                                >{{ stat.label }}</span
                            >
                        </span>
                    </button>
                </div>

                <!-- Panel: active tab blends into this -->
                <div
                    class="flex min-h-0 flex-1 flex-col overflow-hidden border border-gray-100 bg-white shadow-sm md:border-t-0"
                >
                    <!-- Panel Body -->
                    <div
                        v-if="appointments.length === 0"
                        class="flex flex-1 flex-col items-center justify-center py-20"
                    >
                        <CalendarDaysIcon
                            class="mb-3 h-10 w-10 text-gray-200"
                        />
                        <p class="text-sm font-medium text-gray-400">
                            No appointments here
                        </p>
                        <p class="mt-1 text-xs text-gray-300">
                            This category is currently empty
                        </p>
                    </div>

                    <div
                        v-else
                        class="flex-1 divide-y divide-gray-100 overflow-y-auto"
                    >
                        <div
                            v-for="apt in appointments"
                            :key="apt.id"
                            role="button"
                            tabindex="0"
                            class="group flex h-20 cursor-pointer items-center gap-4 px-5 transition-colors duration-150 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                            @click="openProfile(apt)"
                            @keydown.enter="openProfile(apt)"
                            @keydown.space.prevent="openProfile(apt)"
                        >
                            <!-- Avatar -->
                            <div
                                class="bg-sidebar/10 group-hover:bg-sidebar/20 flex h-10 w-10 shrink-0 items-center justify-center transition-colors"
                            >
                                <span class="text-sidebar text-sm font-bold">{{
                                    apt.student_summary.initials
                                }}</span>
                            </div>

                            <!-- Info -->
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-text-primary group-hover:text-sidebar truncate text-sm font-semibold transition-colors"
                                >
                                    {{ apt.student_name }}
                                </p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        {{ apt.context }}
                                    </span>
                                    <span
                                        class="text-text-muted flex items-center gap-1 text-xs"
                                    >
                                        <CalendarDaysIcon
                                            class="h-3 w-3 shrink-0 text-gray-300"
                                        />
                                        {{ formatDateTime(apt.date, apt.time) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex shrink-0 items-center gap-2"
                                @click.stop
                            >
                                <template v-if="activeTab === 'scheduled'">
                                    <span
                                        class="bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                                    >
                                        Scheduled
                                    </span>
                                    <button
                                        type="button"
                                        :disabled="!apt.can_check_in"
                                        :title="
                                            apt.can_check_in
                                                ? 'Show the check-in QR for this session'
                                                : `Available at ${apt.time}`
                                        "
                                        class="flex items-center gap-1.5 border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-600 transition-colors hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:hover:bg-transparent"
                                        @click="openCheckInQr(apt)"
                                    >
                                        <QrCodeIcon class="h-4 w-4" />
                                        Check-in QR
                                    </button>
                                    <button
                                        type="button"
                                        title="Test: open the QR popup ignoring the time window"
                                        class="flex items-center gap-1.5 border border-dashed border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-500 transition-colors hover:bg-gray-50"
                                        @click="openCheckInQrTest(apt)"
                                    >
                                        <QrCodeIcon class="h-4 w-4" />
                                        Test QR
                                    </button>
                                </template>
                                <template v-else-if="activeTab === 'missed'">
                                    <span
                                        class="bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700"
                                    >
                                        Missed
                                    </span>
                                </template>
                                <template v-else>
                                    <span
                                        :class="[
                                            'px-2.5 py-1 text-xs font-semibold',
                                            STATUS_BADGE[apt.status] ??
                                                'bg-gray-100 text-gray-600',
                                        ]"
                                    >
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

            <!-- Right: Schedule Panel -->
            <div
                class="mb-6 flex h-[calc(100dvh-13rem)] min-h-112 w-full shrink-0 flex-col overflow-hidden border border-gray-100 bg-white shadow-sm md:w-72"
            >
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-text-primary text-sm font-semibold">
                            Available Slots
                        </h3>
                        <span
                            class="bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
                        >
                            {{ availableSlots.length }}
                        </span>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
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
                                'group flex items-center justify-between p-3 transition-colors',
                                slot.taken
                                    ? 'bg-red-50'
                                    : 'bg-gray-50 hover:bg-gray-100',
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    :class="[
                                        'h-2 w-2 shrink-0',
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
                                    <p class="mt-0.5 text-[11px] text-gray-400">
                                        {{ slot.start_time }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <span
                                    v-if="slot.taken"
                                    class="bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-500"
                                >
                                    Taken
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    class="p-1.5 text-red-400 transition-colors hover:bg-red-100 hover:text-red-600"
                                    @click="deleteSlot(slot.id)"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 p-4">
                    <button
                        type="button"
                        class="bg-sidebar hover:bg-sidebar/90 w-full cursor-pointer py-2.5 text-sm font-semibold text-white transition-colors"
                        @click="showScheduleModal = true"
                    >
                        + Add Time Slot
                    </button>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CheckInQrModal
            v-if="qrAppointment"
            :appointment="qrAppointment"
            :qr-data-url="qrDataUrl"
            :checked-in="qrCheckedIn"
            @close="closeCheckInQr"
        />

        <StudentProfileModal
            v-if="profileOpen"
            :student="studentProfile ?? null"
            :loading="profileLoading"
            :status-badge="STATUS_BADGE"
            @close="closeProfile"
        />

        <AddSlotModal
            v-if="showScheduleModal"
            @close="showScheduleModal = false"
            @saved="showScheduleModal = false"
        />
    </AdminLayout>
</template>
