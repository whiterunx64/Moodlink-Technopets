<script setup lang="ts">
import DashboardSkeleton from '@/Components/Dashboard/DashboardSkeleton.vue';
import MoodEntry from '@/Components/Dashboard/MoodEntry.vue';
import MoodTrends from '@/Components/Dashboard/MoodTrends.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    AcademicCapIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';
import { usePollingReload } from '@/composables/usePolling';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, type Component } from 'vue';
import type { DashboardPageProps } from '@/types';

const props = defineProps<DashboardPageProps>();

type StatKey =
    | 'moodLogsToday'
    | 'activeStudents'
    | 'flaggedPosts'
    | 'escalationRequests';

const STAT_CARDS: Array<{
    key: StatKey;
    title: string;
    icon: Component;
    color: 'blue' | 'green' | 'red' | 'orange';
    changeUp: boolean;
}> = [
        {
            key: 'moodLogsToday',
            title: 'Mood Logs Today',
            icon: HeartIcon,
            color: 'blue',
            changeUp: true,
        },
        {
            key: 'activeStudents',
            title: 'Active Students',
            icon: AcademicCapIcon,
            color: 'green',
            changeUp: true,
        },
        {
            key: 'flaggedPosts',
            title: 'Flagged Posts',
            icon: ExclamationTriangleIcon,
            color: 'red',
            changeUp: false,
        },
        {
            key: 'escalationRequests',
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
    'moodLogsToday',
    'activeStudents',
    'flaggedPosts',
    'escalationRequests',
    'moodEntries',
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
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard v-for="card in statCards" :key="card.key" :title="card.title" :value="card.value"
                        :icon="card.icon" :color="card.color" :change="card.change" :change-up="card.changeUp" />
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <div class="border-border-light flex flex-col rounded-2xl border bg-white shadow-sm xl:col-span-2">
                        <div class="border-border-light flex items-center justify-between border-b px-5 pt-5 pb-4">
                            <div>
                                <h3 class="text-text-primary text-base font-semibold">
                                    MoodSpace Feed
                                </h3>
                                <p class="text-text-muted mt-0.5 text-xs">
                                    Today's student mood entries
                                </p>
                            </div>
                        </div>

                        <div class="flex max-h-96 flex-1 flex-col gap-3 overflow-y-auto p-4">
                            <MoodEntry v-for="entry in moodEntries" :key="entry.id" :name="entry.name"
                                :time="entry.time" :mood="entry.mood" :message="entry.message"
                                :flagged="entry.flagged" />
                        </div>

                        <div class="border-border-light border-t px-5 py-3">
                            <a :href="route('post-management.index')"
                                class="text-sidebar text-xs font-medium hover:underline">
                                View all posts →
                            </a>
                        </div>
                    </div>

                    <div>
                        <MoodTrends :data="moodTrends" />
                    </div>
                </div>

                <div class="border-border-light rounded-2xl border bg-white shadow-sm">
                    <div class="border-border-light flex items-center justify-between border-b px-5 pt-5 pb-4">
                        <h3 class="text-text-primary text-base font-semibold">
                            Upcoming Appointments
                        </h3>
                    </div>
                    <div class="divide-border-light max-h-96 divide-y overflow-y-auto">
                        <div v-for="apt in appointments" :key="apt.id"
                            class="flex items-center justify-between px-5 py-3.5 transition-colors hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div
                                    class="bg-avatar-bg text-text-primary flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                                    {{ apt.name?.charAt(0) ?? '?' }}
                                </div>
                                <div>
                                    <p class="text-text-primary text-sm font-medium">
                                        {{ apt.name }}
                                    </p>
                                    <p class="text-text-muted text-xs">
                                        {{ apt.date }} · {{ apt.time }}
                                    </p>
                                </div>
                            </div>
                            <span :class="[
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                apt.style,
                            ]">
                                {{ apt.label }}
                            </span>
                        </div>
                        <div v-if="appointments.length === 0" class="text-text-muted py-12 text-center text-sm">
                            No upcoming appointments
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
