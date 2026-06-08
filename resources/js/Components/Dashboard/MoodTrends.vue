<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface DistributionItem {
  label: string;
  pct: number;
  color: string;
}

export interface MoodTrendsData {
  period: 'Today' | 'Weekly' | 'Monthly';
  section: string;
  sections: string[];
  total: number;
  distribution: DistributionItem[];
}

const props = defineProps<{
  /** Mood-distribution aggregate from AdminDashboardService, server-rendered. */
  data: MoodTrendsData;
}>();

const tabs = ['Today', 'Weekly', 'Monthly'] as const;

/** Loading flag for the in-flight reload. */
const loading = ref(false);

/**
 * Optimistic selection: the clicked tab/pill highlights immediately so the
 * widget feels instant, while Laravel re-aggregates server-side. Once the
 * fresh `moodTrends` prop lands these fall back to the server's values.
 */
const pendingPeriod = ref<string | null>(null);
const pendingSection = ref<string | null>(null);

const activePeriod = () => pendingPeriod.value ?? props.data.period;
const activeSection = () => pendingSection.value ?? props.data.section;

/**
 * Server-side rendering: tab/section changes are sent back to Laravel, which
 * re-aggregates and ships a fresh `moodTrends` prop. No client-side filtering.
 */
function applyFilter(period: string, section: string) {
  // Ignore no-op clicks so re-selecting the active filter doesn't hit the DB.
  if (period === activePeriod() && section === activeSection()) return;

  pendingPeriod.value = period;
  pendingSection.value = section;

  router.reload({
    only: ['moodTrends'],
    data: { trendPeriod: period, trendSection: section },
    preserveUrl: true,
    onStart: () => { loading.value = true; },
    onFinish: () => {
      loading.value = false;
      pendingPeriod.value = null;
      pendingSection.value = null;
    },
  });
}
</script>

<template>
  <div class="bg-white rounded-2xl border border-border-light shadow-sm flex flex-col h-full">
    <!-- Header -->
    <div class="px-5 pt-5 pb-4 border-b border-border-light flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold text-text-primary">Mood Trends</h3>
        <p class="text-xs text-text-muted mt-0.5">Emotional distribution overview</p>
      </div>
      <i v-if="loading" class="fas fa-circle-notch fa-spin text-sm text-text-muted" />
    </div>

    <div class="p-5 flex flex-col flex-1 gap-4">
      <!-- Tabs -->
      <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
        <button v-for="tab in tabs" :key="tab" type="button" :disabled="loading" :class="[
          'flex-1 py-1.5 text-xs font-medium rounded-md transition-all duration-150 disabled:cursor-not-allowed',
          activePeriod() === tab
            ? 'bg-white text-text-primary shadow-sm'
            : 'text-text-muted hover:text-text-secondary',
        ]" @click="applyFilter(tab, activeSection())">
          {{ tab }}
        </button>
      </div>

      <!-- Section Pills -->
      <div class="flex flex-wrap gap-1.5">
        <button v-for="s in props.data.sections" :key="s" type="button" :disabled="loading" :class="[
          'px-3 py-1 text-xs rounded-full font-medium transition-all duration-150 disabled:cursor-not-allowed',
          activeSection() === s
            ? 'bg-sidebar text-white'
            : 'bg-pill-inactive-bg text-pill-inactive-text hover:bg-pill-inactive-bg-hover',
        ]" @click="applyFilter(activePeriod(), s)">
          {{ s }}
        </button>
      </div>

      <!-- Mood Distribution Bars -->
      <div v-if="props.data.total > 0"
        :class="['flex-1 flex flex-col justify-center gap-3 transition-opacity duration-150', loading ? 'opacity-50' : '']">
        <div v-for="item in props.data.distribution" :key="item.label" class="space-y-1">
          <div class="flex justify-between text-xs">
            <span class="font-medium text-text-secondary">{{ item.label }}</span>
            <span class="text-text-muted">{{ item.pct }}%</span>
          </div>
          <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
            <div :class="['h-full rounded-full transition-all duration-500', item.color]"
              :style="{ width: item.pct + '%' }" />
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="flex-1 flex flex-col items-center justify-center text-center gap-1 py-6">
        <i class="fas fa-chart-simple text-2xl text-text-muted/50" />
        <p class="text-xs text-text-muted">No mood logs for this period</p>
      </div>

      <!-- Legend -->
      <div class="grid grid-cols-2 gap-2 pt-2 border-t border-border-light">
        <div v-for="item in props.data.distribution" :key="item.label" class="flex items-center gap-1.5">
          <div :class="['w-2.5 h-2.5 rounded-full shrink-0', item.color]" />
          <span class="text-xs text-text-muted">{{ item.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
