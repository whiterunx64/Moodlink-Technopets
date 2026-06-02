<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MoodEntry from '@/Components/Dashboard/MoodEntry.vue';
import MoodTrends from '@/Components/Dashboard/MoodTrends.vue';
import { getTodayLocalDate } from '@/utils/date';
import { supabase } from '@/utils/supabase';
import { useRealtime } from '@/composables/useRealtime';

type Mood = 'happy' | 'sad' | 'anxious' | 'neutral';
type AppointmentType = 'Urgent' | 'Consultation';

interface MoodEntryData {
  name: string;
  time: string;
  mood: Mood;
  flagged: boolean;
  message: string;
}

interface AppointmentData {
  name: string;
  time: string;
  date: string;
  type: AppointmentType;
}

// Dashboard stat cards bound to realtime-updated values
const STAT_CARDS = [
  { key: 'moodLogsToday', title: 'Mood Logs Today', icon: 'fas fa-heartbeat', color: 'blue' as const, changeUp: true },
  { key: 'activeStudents', title: 'Active Students', icon: 'fas fa-user-graduate', color: 'green' as const, changeUp: true },
  { key: 'flaggedPosts', title: 'Flagged Posts', icon: 'fas fa-exclamation-triangle', color: 'red' as const, changeUp: false },
  { key: 'escalationRequests', title: 'Escalation Requests', icon: 'fas fa-clock', color: 'orange' as const, changeUp: false },
] as const;

type StatKey = typeof STAT_CARDS[number]['key'];

// Initialize StatCard counters to zero by default
const statValues = ref<Record<StatKey, number>>(
  Object.fromEntries(STAT_CARDS.map(c => [c.key, 0])) as Record<StatKey, number>
);

const statCards = computed(() =>
  STAT_CARDS.map(card => ({ ...card, value: statValues.value[card.key], change: null }))
);

const moodEntries: MoodEntryData[] = [
  { name: 'Anonymous Tabayoyon', time: '12:00 PM', mood: 'happy', flagged: false, message: 'Group study session was productive today. Feeling more confident about the upcoming exam.' },
  { name: 'Anonymous Mountain', time: '11:30 AM', mood: 'sad', flagged: true, message: 'Having a hard time adjusting. Feeling isolated from everyone and thinking about giving up.' },
  { name: 'Anonymous River', time: '11:00 AM', mood: 'happy', flagged: false, message: 'Starting to enjoy my new course! The professor is very engaging and the topics are interesting.' },
  { name: 'Anonymous Storm', time: '10:00 AM', mood: 'anxious', flagged: true, message: "I don't know how to cope anymore. Everything feels like it's falling apart and I can't focus on anything." },
];

const appointments: AppointmentData[] = [
  { name: 'Anonymous Mountain', time: '2:00 PM', date: 'Today', type: 'Urgent' },
  { name: 'Anonymous Coral', time: '3:30 PM', date: 'Today', type: 'Consultation' },
  { name: 'Anonymous Pine', time: '9:00 AM', date: 'Tomorrow', type: 'Consultation' },
];

const { subscribe, unsubscribeAll } = useRealtime();

async function fetchCounts() {
  const today = getTodayLocalDate();
  const [moodLogs, activeStudents, flaggedPosts, escalations] = await Promise.all([
    supabase.from('posts').select('*', { count: 'exact', head: true }).gte('created_at', today),
    supabase.from('students').select('*', { count: 'exact', head: true }).eq('status', 'verified'),
    supabase.from('posts').select('*', { count: 'exact', head: true }).eq('isReported', true),
    supabase.from('appointments').select('*', { count: 'exact', head: true }).eq('status', 'Pending'),
  ]);

  statValues.value = {
    moodLogsToday: moodLogs.count ?? 0,
    activeStudents: activeStudents.count ?? 0,
    flaggedPosts: flaggedPosts.count ?? 0,
    escalationRequests: escalations.count ?? 0,
  };
}

onMounted(async () => {
  await fetchCounts();

  subscribe('posts', fetchCounts);
  subscribe('students', fetchCounts);
  subscribe('appointments', fetchCounts);
});

onUnmounted(() => unsubscribeAll());
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
            <span class="text-xs bg-red-50 text-red-500 font-semibold px-2.5 py-1 rounded-full">
              2 flagged
            </span>
          </div>

          <div class="p-4 flex-1 flex flex-col gap-3 overflow-y-auto max-h-96">
            <MoodEntry v-for="(entry, i) in moodEntries" :key="i" v-bind="entry" />
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
            <div v-for="(apt, i) in appointments" :key="i"
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
              <span :class="[
                'text-xs font-semibold px-2.5 py-1 rounded-full',
                apt.type === 'Urgent' ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500',
              ]">
                {{ apt.type }}
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
            <p class="text-4xl font-extrabold text-text-primary">1</p>
            <p class="text-sm text-text-muted">Active escalation request</p>
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
