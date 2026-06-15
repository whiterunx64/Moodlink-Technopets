<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, type Component } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AcademicCapIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MoodEntry from '@/Components/Dashboard/MoodEntry.vue';
import MoodTrends, { type MoodTrendsData } from '@/Components/Dashboard/MoodTrends.vue';
import DashboardSkeleton from '@/Components/Dashboard/DashboardSkeleton.vue';


const props = defineProps<{
  /**
   * StatCard data from Laravel controller via Inertia
   */
  moodLogsToday: number;
  activeStudents: number;
  flaggedPosts: number;
  escalationRequests: number;
  /**
  * MoodEntry data from Laravel controller via Inertia
  */
  moodEntries: Array<{
    id: string;
    mood: 'happy' | 'sad' | 'anxious' | 'neutral';
    message: string;
    time: string;
    name: string;
    flagged: boolean;
  }>;
  /**
  * Widget data from Laravel controller via Inertia
  */
  appointments: Array<{
    id: string;
    name: string;
    time: string;
    date: string;
    label: 'Urgent' | 'Consultation';
    style: string;
  }>;
  /**
  * MoodTrends aggregate from Laravel controller via Inertia
  */
  moodTrends: MoodTrendsData;
}>();

/** 
 * StatCard Submodule UI mapping and reactive state section
 **/
type StatKey = 'moodLogsToday' | 'activeStudents' | 'flaggedPosts' | 'escalationRequests';

const STAT_CARDS: Array<{ key: StatKey; title: string; icon: Component; color: 'blue' | 'green' | 'red' | 'orange'; changeUp: boolean }> = [
  { key: 'moodLogsToday',       title: 'Mood Logs Today',       icon: HeartIcon,               color: 'blue',   changeUp: true },
  { key: 'activeStudents',      title: 'Active Students',       icon: AcademicCapIcon,          color: 'green',  changeUp: true },
  { key: 'flaggedPosts',        title: 'Flagged Posts',         icon: ExclamationTriangleIcon,  color: 'red',    changeUp: false },
  { key: 'escalationRequests',  title: 'Escalation Requests',   icon: ClockIcon,                color: 'orange', changeUp: false },
];

const statCards = computed(() =>
  STAT_CARDS.map(card => ({
    ...card,
    value: props[card.key] ?? 0,
    change: null,
  }))
);

function refresh() {
  router.reload({
    only: ['moodLogsToday', 'activeStudents', 'flaggedPosts', 'escalationRequests', 'moodEntries', 'appointments'],
    preserveUrl: true,
  });
}

let pollTimer: ReturnType<typeof setInterval> | null = null;

const loading = ref(true);

onMounted(() => {
  const t = setTimeout(() => { loading.value = false; }, 700);
  pollTimer = setInterval(refresh, 15_000);
  onUnmounted(() => clearTimeout(t));
});
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });
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
      <!-- Skeleton -->
      <DashboardSkeleton v-if="loading" />

      <!-- Real content -->
      <div v-else class="space-y-6">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <StatCard v-for="card in statCards" :key="card.key" :title="card.title" :value="card.value" :icon="card.icon"
            :color="card.color" :change="card.change" :change-up="card.changeUp" />
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

          <!-- MoodSpace Feed -->
          <div class="xl:col-span-2 bg-white rounded-2xl border border-border-light shadow-sm flex flex-col">
            <div class="px-5 pt-5 pb-4 border-b border-border-light flex items-center justify-between">
              <div>
                <h3 class="text-base font-semibold text-text-primary">MoodSpace Feed</h3>
                <p class="text-xs text-text-muted mt-0.5">Today's student mood entries</p>
              </div>
            </div>

            <div class="p-4 flex-1 flex flex-col gap-3 overflow-y-auto max-h-96">
              <MoodEntry v-for="entry in moodEntries" :key="entry.id" :name="entry.name" :time="entry.time"
                :mood="entry.mood" :message="entry.message" :flagged="entry.flagged" />
            </div>

            <div class="px-5 py-3 border-t border-border-light">
              <a :href="route('post-management.index')" class="text-xs text-sidebar font-medium hover:underline">
                View all posts →
              </a>
            </div>
          </div>

          <!-- Mood Trends -->
          <div>
            <MoodTrends :data="moodTrends" />
          </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-2xl border border-border-light shadow-sm">
          <div class="px-5 pt-5 pb-4 border-b border-border-light flex items-center justify-between">
            <h3 class="text-base font-semibold text-text-primary">Upcoming Appointments</h3>
            <!-- <a :href="route('appointments.index')" class="text-xs text-sidebar font-medium hover:underline">View all →</a> -->
          </div>
          <div class="divide-y divide-border-light overflow-y-auto max-h-96">
            <div v-for="apt in appointments" :key="apt.id"
              class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full bg-avatar-bg flex items-center justify-center text-xs font-bold text-text-primary shrink-0">
                  {{ apt.name?.charAt(0) ?? '?' }}
                </div>
                <div>
                  <p class="text-sm font-medium text-text-primary">{{ apt.name }}</p>
                  <p class="text-xs text-text-muted">{{ apt.date }} · {{ apt.time }}</p>
                </div>
              </div>
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', apt.style]">
                {{ apt.label }}
              </span>
            </div>
            <div v-if="appointments.length === 0" class="py-12 text-center text-sm text-text-muted">
              No upcoming appointments
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>