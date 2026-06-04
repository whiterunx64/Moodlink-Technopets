<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MoodEntry from '@/Components/Dashboard/MoodEntry.vue';
import MoodTrends from '@/Components/Dashboard/MoodTrends.vue';


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
}>();

/** 
 * StatCard Submodule UI mapping and reactive state section
 **/
const STAT_CARDS = [
  { key: 'moodLogsToday', title: 'Mood Logs Today', icon: 'fas fa-heartbeat', color: 'blue' as const, changeUp: true },
  { key: 'activeStudents', title: 'Active Students', icon: 'fas fa-user-graduate', color: 'green' as const, changeUp: true },
  { key: 'flaggedPosts', title: 'Flagged Posts', icon: 'fas fa-exclamation-triangle', color: 'red' as const, changeUp: false },
  { key: 'escalationRequests', title: 'Escalation Requests', icon: 'fas fa-clock', color: 'orange' as const, changeUp: false },
] as const;

type StatKey = typeof STAT_CARDS[number]['key'];

const statCards = computed(() =>
  STAT_CARDS.map(card => ({
    ...card,
    value: props[card.key as StatKey] ?? 0,
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

onMounted(() => { pollTimer = setInterval(refresh, 15_000); });
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer); });
</script>

<template>

  <Head title="Dashboard" />

  <AdminLayout title="Dashboard">
    <div class="space-y-6">

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
            <a href="/posts" class="text-xs text-sidebar font-medium hover:underline">
              View all posts →
            </a>
          </div>
        </div>

        <!-- Mood Trends -->
        <div class="flex flex-col">
          <MoodTrends class="flex-1" />
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Upcoming Appointments -->
        <div class="sm:col-span-2 xl:col-span-2 bg-white rounded-2xl border border-border-light shadow-sm">
          <div class="px-5 pt-5 pb-4 border-b border-border-light flex items-center justify-between">
            <h3 class="text-base font-semibold text-text-primary">Upcoming Appointments</h3>
            <a href="/appointments" class="text-xs text-sidebar font-medium hover:underline">View all →</a>
          </div>
          <div class="divide-y divide-border-light">
            <div v-for="apt in appointments" :key="apt.id"
              class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full bg-avatar-bg flex items-center justify-center text-xs font-bold text-text-primary shrink-0">
                  {{ apt.name.charAt(0) }}
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
          </div>
        </div>

        <!-- Escalation Summary -->
        <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex flex-col gap-4">
          <h3 class="text-base font-semibold text-text-primary">Escalation</h3>
          <div class="flex-1 flex flex-col justify-center items-center text-center gap-2">
            <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center">
              <i class="fas fa-exclamation-circle text-2xl text-orange-400" />
            </div>
            <p class="text-4xl font-extrabold text-text-primary">{{ escalationRequests }}</p>
            <p class="text-sm text-text-muted">
              Active escalation request{{ escalationRequests !== 1 ? 's' : '' }}
            </p>
          </div>
          <button type="button"
            class="w-full py-2 rounded-xl bg-sidebar text-white text-sm font-medium hover:bg-primary-hover transition-colors">
            Review Now
          </button>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>