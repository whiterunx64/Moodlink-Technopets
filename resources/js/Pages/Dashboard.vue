<script setup lang="ts">
import DashboardSkeleton from '@/Components/Dashboard/DashboardSkeleton.vue';
import MoodEntry from '@/Components/Dashboard/MoodEntry.vue';
import MoodTrends from '@/Components/Dashboard/MoodTrends.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import { usePollingReload } from '@/composables/usePolling';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { DashboardPageProps, PageProps } from '@/types';
import {
    AcademicCapIcon,
    CalendarIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, type Component } from 'vue';

const props = defineProps<DashboardPageProps>();

const page = usePage<PageProps>();
const adminName = computed(() => {
    const full = page.props.auth?.user?.name ?? '';
    return full.split(' ')[0] || full;
});

type StatKey =
    | 'mood_logs_today'
    | 'active_students'
    | 'flagged_posts'
    | 'escalation_requests';

const STAT_CARDS: Array<{
    key: StatKey;
    title: string;
    icon: Component;
    color: 'blue' | 'green' | 'red' | 'orange';
    changeUp: boolean;
}> = [
        {
            key: 'mood_logs_today',
            title: 'Mood Logs Today',
            icon: HeartIcon,
            color: 'blue',
            changeUp: true,
        },
        {
            key: 'active_students',
            title: 'Active Students',
            icon: AcademicCapIcon,
            color: 'green',
            changeUp: true,
        },
        {
            key: 'flagged_posts',
            title: 'Flagged Posts',
            icon: ExclamationTriangleIcon,
            color: 'red',
            changeUp: false,
        },
        {
            key: 'escalation_requests',
            title: 'Escalation Requests',
            icon: ClockIcon,
            color: 'orange',
            changeUp: false,
        },
    ];

const statCards = computed(() =>
    STAT_CARDS.map((card) => ({
        ...card,
        value: props[card.key] ?? 0,
        change: null,
    })),
);

usePollingReload([
    'mood_logs_today',
    'active_students',
    'flagged_posts',
    'escalation_requests',
    'mood_entries',
    'appointments',
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
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0" mode="out-in">
            <DashboardSkeleton v-if="loading" />

            <div v-else class="space-y-6">
                <!-- Welcome banner -->
                <div
                    class="border-border-light flex items-center gap-4 rounded-2xl border bg-white px-6 py-4 shadow-sm">
                    <div
                        class="bg-sidebar flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white">
                        {{ adminName.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-text-primary text-xl font-bold">
                            Welcome back, {{ adminName }}!
                        </p>
                        <p class="text-text-muted mt-0.5 text-sm">
                            Here's what's happening on MoodLink today.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-4">
                    <StatCard v-for="card in statCards" :key="card.key" :title="card.title" :value="card.value"
                        :icon="card.icon" :color="card.color" :change="card.change" :change-up="card.changeUp" />
                </div>

                <div class="flex-row gap-6 md:flex md:h-136">
                    <div class="border-border-light flex h-full flex-1 flex-col rounded-2xl border bg-white shadow-sm">
                        <div
                            class="border-border-light flex shrink-0 items-center justify-between border-b px-5 pt-5 pb-4">
                            <div>
                                <h3 class="text-text-primary text-base font-semibold">
                                    MoodSpace Feed
                                </h3>
                                <p class="text-text-muted mt-0.5 text-xs">
                                    Today's student mood entries
                                </p>
                            </div>
                        </div>

                        <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto p-4">
                            <MoodEntry v-for="entry in mood_entries" :key="entry.id" :name="entry.name"
                                :time="entry.time" :mood="entry.mood" :message="entry.message"
                                :flagged="entry.flagged" />
                            <div v-if="mood_entries.length === 0"
                                class="text-text-muted flex flex-1 flex-col items-center justify-center gap-3 text-sm">
                                <HeartIcon class="h-16 w-16 text-blue-500/60" aria-hidden="true" />
                                No mood entries today
                            </div>
                        </div>

                        <div class="border-border-light shrink-0 border-t px-5 py-3">
                            <a :href="route('posts.index')" class="text-sidebar text-xs font-medium hover:underline">
                                View all posts →
                            </a>
                        </div>
                    </div>

                    <div class="h-full">
                        <MoodTrends :data="mood_trends" />
                    </div>
                </div>

                <div class="border-border-light rounded-2xl border bg-white shadow-sm">
                    <div class="border-border-light flex items-center justify-between border-b px-5 pt-5 pb-4">
                        <h3 class="text-text-primary text-base font-semibold">
                            Upcoming Appointments
                        </h3>
                    </div>
                    <div class="divide-border-light flex h-128 flex-col divide-y overflow-y-auto">
                        <div v-for="apt in appointments" :key="apt.id"
                            class="flex items-center justify-between gap-3 px-5 py-3.5 transition-colors hover:bg-gray-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="bg-avatar-bg text-text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                                    {{ apt.name?.charAt(0) ?? '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-text-primary truncate text-sm font-medium">
                                        {{ apt.name }}
                                    </p>
                                    <p class="text-text-muted truncate text-xs">
                                        {{ apt.date }} · {{ apt.time }}
                                    </p>
                                </div>
                            </div>
                            <span :class="[
                                'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                apt.style,
                            ]">
                                {{ apt.label }}
                            </span>
                        </div>
                        <div v-if="appointments.length === 0"
                            class="text-text-muted flex flex-1 flex-col items-center justify-center gap-3 text-sm">
                            <CalendarIcon class="h-16 w-16 text-blue-500/60" aria-hidden="true" />
                            No upcoming appointments
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>