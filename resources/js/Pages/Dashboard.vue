<script setup lang="ts">
import DashboardSkeleton from '@/Components/Dashboard/DashboardSkeleton.vue';
import MoodTrends from '@/Components/Dashboard/MoodTrends.vue';
import type { BreakdownItem } from '@/Components/Dashboard/StatCard.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import { usePollingReload } from '@/composables/usePolling';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { DashboardPageProps } from '@/types';
import {
    AcademicCapIcon,
    CalendarDaysIcon,
    CalendarIcon,
    ChatBubbleLeftEllipsisIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, type Component } from 'vue';

const props = defineProps<DashboardPageProps>();

// ── Mood leading badge color ───────────────────────────────────────────────────
const MOOD_BADGE: Record<string, string> = {
    Excited: 'bg-green-100 text-green-700',
    Content: 'bg-blue-100 text-blue-700',
    Stressed: 'bg-yellow-100 text-yellow-700',
    Drained: 'bg-red-100 text-red-700',
};

const leadingMoodColor = computed(() =>
    props.mood_logs_breakdown.leading
        ? (MOOD_BADGE[props.mood_logs_breakdown.leading] ??
          'bg-gray-100 text-gray-500')
        : '',
);

// ── Stat cards config ─────────────────────────────────────────────────────────
interface StatCardConfig {
    label: string;
    value: number;
    icon: Component;
    iconBg: string;
    iconColor: string;
    cardBg: string;
    borderColor: string;
    hoverRing: string;
    href: string;
    rows: BreakdownItem[];
}

const statCards = computed<StatCardConfig[]>(() => [
    {
        label: 'Mood Logs',
        value: props.mood_logs_breakdown.total,
        icon: HeartIcon,
        iconBg: 'bg-green-200',
        iconColor: 'text-green-700',
        cardBg: 'bg-green-50',
        borderColor: 'border-green-100',
        hoverRing: 'hover:ring-2 hover:ring-green-300',
        href: route('reports.index'),
        rows: [
            {
                type: 'badge',
                label: 'Leading mood',
                value: props.mood_logs_breakdown.leading,
                color: leadingMoodColor.value,
            },
        ],
    },
    {
        label: 'Users',
        value: props.students_breakdown.total - 1,
        icon: AcademicCapIcon,
        iconBg: 'bg-blue-200',
        iconColor: 'text-blue-700',
        cardBg: 'bg-blue-50',
        borderColor: 'border-blue-100',
        hoverRing: 'hover:ring-2 hover:ring-blue-300',
        href: route('student-accounts.index'),
        rows: [
            {
                type: 'pill',
                label: 'Active',
                count: props.students_breakdown.active - 1,
                variant: 'green',
            },
            {
                type: 'pill',
                label: 'Pending',
                count: props.students_breakdown.pending,
                variant: 'orange',
            },
            {
                type: 'pill',
                label: 'Suspended',
                count: props.students_breakdown.suspended,
                variant: 'neutral',
            },
        ],
    },
    {
        label: 'Posts',
        value: props.posts_breakdown.total,
        icon: ChatBubbleLeftEllipsisIcon,
        iconBg: 'bg-red-200',
        iconColor: 'text-red-700',
        cardBg: 'bg-red-50',
        borderColor: 'border-red-100',
        hoverRing: 'hover:ring-2 hover:ring-red-300',
        href: route('posts.index'),
        rows: [
            {
                type: 'pill',
                label: 'Safe',
                count: props.posts_breakdown.safe,
                variant: 'green',
            },
            {
                type: 'pill',
                label: 'Flagged',
                count: props.posts_breakdown.flagged,
                variant: 'red',
            },
            {
                type: 'rate',
                label: 'Flag rate',
                value: props.posts_breakdown.flag_rate,
                dotColor: 'bg-red-500',
            },
        ],
    },
    {
        label: 'Appointments',
        value: props.appointments_breakdown.total,
        icon: CalendarDaysIcon,
        iconBg: 'bg-violet-200',
        iconColor: 'text-violet-700',
        cardBg: 'bg-violet-50',
        borderColor: 'border-violet-100',
        hoverRing: 'hover:ring-2 hover:ring-violet-300',
        href: route('appointments.index'),
        rows: [
            {
                type: 'pill',
                label: 'Scheduled',
                count: props.appointments_breakdown.scheduled,
                variant: 'green',
            },
            {
                type: 'pill',
                label: 'Pending',
                count: props.appointments_breakdown.pending,
                variant: 'orange',
            },
            {
                type: 'pill',
                label: 'Missed',
                count: props.appointments_breakdown.missed,
                variant: 'red',
            },
        ],
    },
]);

// ── Stat period filter ─────────────────────────────────────────────────────────
const STAT_PERIODS = [
    { key: 'today' as const, label: 'Today' },
    { key: 'week' as const, label: 'This Week' },
    { key: 'month' as const, label: 'This Month' },
];

const TREND_PERIOD_MAP: Record<string, string> = {
    today: 'Today',
    week: 'Weekly',
    month: 'Monthly',
};

function setStatPeriod(period: 'today' | 'week' | 'month') {
    router.get(
        route('dashboard'),
        {
            statPeriod: period,
            trendPeriod: TREND_PERIOD_MAP[period],
            trendProgram: props.mood_trends.program,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: [
                'stat_period',
                'mood_logs_breakdown',
                'posts_breakdown',
                'appointments_breakdown',
                'mood_trends',

                // add these
                'mood_entries',
                'flagged_mood_entries',
                'activity_appointments',
            ],
        },
    );
}

// ── Recent activity tabs ───────────────────────────────────────────────────────
type ActivityTab = 'feed' | 'appointments' | 'flagged';

const ACTIVITY_TABS: { key: ActivityTab; label: string }[] = [
    { key: 'feed', label: 'MoodSpace feed' },
    { key: 'appointments', label: 'Appointments' },
    { key: 'flagged', label: 'Flagged posts' },
];

function setActivityTab(tab: ActivityTab) {
    router.get(
        route('dashboard'),
        { activityTab: tab },
        {
            preserveState: true,
            preserveScroll: true,
            only: [
                'activity_tab',
                'mood_entries',
                'flagged_mood_entries',
                'activity_appointments',
            ],
        },
    );
}

const moodEmoji: Record<string, string> = {
    Excited: '⚡',
    Content: '🍀',
    Stressed: '🌧️',
    Drained: '😤',
};

const APPOINTMENT_STATUS_STYLE: Record<string, string> = {
    Scheduled: 'bg-status-safe-bg text-status-safe',
    Pending: 'bg-orange-100 text-orange-700',
    Missed: 'bg-status-flagged-bg text-status-flagged',
};

const viewAllRoute = computed(() => {
    if (props.activity_tab === 'appointments')
        return route('appointments.index');
    return route('posts.index');
});

// ── Polling ────────────────────────────────────────────────────────────────────
usePollingReload([
    'mood_logs_today',
    'active_students',
    'flagged_posts',
    'escalation_requests',
    'mood_entries',
    'flagged_mood_entries',
    'appointments',
    'stat_period',
    'activity_tab',
    'mood_logs_breakdown',
    'students_breakdown',
    'posts_breakdown',
    'appointments_breakdown',
    'activity_appointments',
]);

const loading = ref(true);
onMounted(() => {
    const t = setTimeout(() => {
        loading.value = false;
    }, 700);
    onUnmounted(() => clearTimeout(t));
});
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout title="Dashboard">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200"
            leave-to-class="opacity-0"
            mode="out-in"
        >
            <DashboardSkeleton v-if="loading" />

            <div v-else class="space-y-5">
                <!-- Stat period selector -->
                <div class="flex items-center gap-2">
                    <span class="text-text-muted text-xs font-medium"
                        >Showing:</span
                    >
                    <div class="flex gap-1 bg-gray-100 p-1">
                        <button
                            v-for="p in STAT_PERIODS"
                            :key="p.key"
                            type="button"
                            :class="[
                                'cursor-pointer px-3 py-1.5 text-xs font-medium transition-colors',
                                stat_period === p.key
                                    ? 'text-text-primary bg-white shadow-sm'
                                    : 'text-text-muted hover:text-text-primary',
                            ]"
                            @click="setStatPeriod(p.key)"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                    <span class="text-text-muted text-xs italic"
                        >Users always shows current totals</span
                    >
                </div>

                <!-- Stat cards — full-width row, natural height -->
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                    <StatCard
                        v-for="card in statCards"
                        :key="card.label"
                        :label="card.label"
                        :value="card.value"
                        :icon="card.icon"
                        :icon-bg="card.iconBg"
                        :icon-color="card.iconColor"
                        :card-bg="card.cardBg"
                        :border-color="card.borderColor"
                        :hover-ring="card.hoverRing"
                        :href="card.href"
                        :rows="card.rows"
                    />
                </div>

                <!-- Bottom panels -->
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[3fr_2fr]">
                    <!-- Recent activity panel -->
                    <div
                        class="border-border-light flex flex-col border bg-white shadow-sm"
                    >
                        <!-- Header + tabs -->
                        <div
                            class="border-border-light shrink-0 border-b px-5 pt-5 pb-0"
                        >
                            <h3
                                class="text-text-primary text-base font-semibold"
                            >
                                Recent activity
                            </h3>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Latest entries across all modules
                            </p>

                            <div class="mt-3 flex" role="tablist">
                                <button
                                    v-for="tab in ACTIVITY_TABS"
                                    :key="tab.key"
                                    type="button"
                                    role="tab"
                                    :aria-selected="activity_tab === tab.key"
                                    :class="[
                                        'relative mr-5 cursor-pointer pb-3 text-sm font-medium transition-colors duration-150',
                                        activity_tab === tab.key
                                            ? 'text-text-primary after:bg-sidebar after:absolute after:right-0 after:bottom-0 after:left-0 after:h-0.5'
                                            : 'text-text-muted hover:text-text-primary',
                                    ]"
                                    @click="setActivityTab(tab.key)"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Tab content -->
                        <div
                            class="divide-border-light divide-y overflow-y-auto"
                            style="height: 360px"
                        >
                            <!-- MoodSpace feed -->
                            <template v-if="activity_tab === 'feed'">
                                <div
                                    v-for="entry in mood_entries"
                                    :key="entry.id"
                                    class="hover:bg-hover-soft flex items-start gap-3 px-5 py-3.5 transition-colors"
                                >
                                    <div
                                        class="bg-avatar-bg text-text-primary flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold select-none"
                                    >
                                        {{ entry.name?.charAt(0) ?? '?' }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-text-primary truncate text-sm font-medium"
                                                    >{{ entry.name }}</span
                                                >
                                                <span
                                                    class="text-text-muted ml-1.5 text-xs"
                                                    >({{
                                                        entry.anonymous_name
                                                    }})</span
                                                >
                                            </div>
                                            <div
                                                class="flex shrink-0 items-center gap-1.5"
                                            >
                                                <span
                                                    v-if="entry.mood"
                                                    class="text-sm leading-none"
                                                    >{{
                                                        moodEmoji[entry.mood] ??
                                                        ''
                                                    }}</span
                                                >
                                                <span
                                                    class="text-text-muted text-xs"
                                                    >{{ entry.time }}</span
                                                >
                                            </div>
                                        </div>
                                        <p
                                            class="text-text-muted mt-0.5 line-clamp-1 text-xs"
                                        >
                                            {{ entry.message }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'shrink-0 self-center px-2 py-0.5 text-xs font-semibold',
                                            entry.flagged
                                                ? 'bg-status-flagged-bg text-status-flagged'
                                                : 'bg-status-safe-bg text-status-safe',
                                        ]"
                                    >
                                        {{ entry.flagged ? 'Flagged' : 'Safe' }}
                                    </span>
                                </div>
                                <div
                                    v-if="mood_entries.length === 0"
                                    class="text-text-muted flex flex-col items-center justify-center gap-2 py-10 text-sm"
                                >
                                    <HeartIcon
                                        class="h-10 w-10 text-blue-400/60"
                                    />
                                    No mood entries today
                                </div>
                            </template>

                            <!-- Appointments -->
                            <template
                                v-else-if="activity_tab === 'appointments'"
                            >
                                <div
                                    v-for="apt in activity_appointments"
                                    :key="apt.id"
                                    class="hover:bg-hover-soft flex items-center gap-3 px-5 py-3.5 transition-colors"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center bg-violet-100 text-xs font-bold text-violet-700 select-none"
                                    >
                                        {{ apt.name?.charAt(0) ?? '?' }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p
                                                class="text-text-primary truncate text-sm font-medium"
                                            >
                                                {{ apt.name }}
                                            </p>
                                            <span
                                                class="text-text-muted shrink-0 text-xs"
                                                >({{
                                                    apt.anonymous_name
                                                }})</span
                                            >
                                        </div>
                                        <p
                                            class="text-text-muted truncate text-xs"
                                        >
                                            {{
                                                apt.context ??
                                                'No context provided'
                                            }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-text-muted text-xs">
                                            {{ apt.date }} {{ apt.time }}
                                        </p>
                                        <span
                                            :class="[
                                                'mt-1 inline-block px-2 py-0.5 text-xs font-semibold',
                                                APPOINTMENT_STATUS_STYLE[
                                                    apt.status
                                                ] ??
                                                    'bg-gray-100 text-gray-500',
                                            ]"
                                        >
                                            {{ apt.status }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    v-if="activity_appointments.length === 0"
                                    class="text-text-muted flex flex-col items-center justify-center gap-2 py-10 text-sm"
                                >
                                    <CalendarIcon
                                        class="h-10 w-10 text-blue-400/60"
                                    />
                                    No appointments found
                                </div>
                            </template>

                            <!-- Flagged posts -->
                            <template v-else>
                                <div
                                    v-for="entry in flagged_mood_entries"
                                    :key="entry.id"
                                    class="hover:bg-hover-soft flex items-start gap-3 px-5 py-3.5 transition-colors"
                                >
                                    <div
                                        class="bg-status-flagged-bg text-status-flagged flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold select-none"
                                    >
                                        {{ entry.name?.charAt(0) ?? '?' }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-center justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <span
                                                    class="text-text-primary truncate text-sm font-medium"
                                                    >{{ entry.name }}</span
                                                >
                                                <span
                                                    class="text-text-muted ml-1.5 text-xs"
                                                    >({{
                                                        entry.anonymous_name
                                                    }})</span
                                                >
                                            </div>
                                            <span
                                                class="text-text-muted shrink-0 text-xs"
                                                >{{ entry.time }}</span
                                            >
                                        </div>
                                        <p
                                            class="text-text-muted mt-0.5 line-clamp-1 text-xs"
                                        >
                                            {{ entry.message }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="flagged_mood_entries.length === 0"
                                    class="text-text-muted flex flex-col items-center justify-center gap-2 py-10 text-sm"
                                >
                                    <ChatBubbleLeftEllipsisIcon
                                        class="h-10 w-10 text-blue-400/60"
                                    />
                                    No flagged posts today
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div
                            class="border-border-light shrink-0 border-t px-5 py-3"
                        >
                            <a
                                :href="viewAllRoute"
                                class="text-text-muted hover:text-text-primary inline-flex items-center gap-1 bg-gray-100 px-4 py-2 text-xs font-semibold transition-colors hover:bg-gray-200"
                            >
                                View all activity →
                            </a>
                        </div>
                    </div>

                    <!-- Mood trend -->
                    <MoodTrends
                        :data="mood_trends"
                        :stat-period="stat_period"
                    />
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
