<script setup lang="ts">
import { computed, ref } from 'vue';
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

const loading = ref(false);
const pendingPeriod = ref<string | null>(null);
const pendingSection = ref<string | null>(null);

const activePeriod = computed(() => pendingPeriod.value ?? props.data.period);
const activeSection = computed(() => pendingSection.value ?? props.data.section);

let inflightController: AbortController | null = null;

function applyFilter(period: string, section: string) {
  if (period === activePeriod.value && section === activeSection.value) return;

  // Cancel any in-flight request before firing a new one
  inflightController?.abort();
  inflightController = new AbortController();

  pendingPeriod.value = period;
  pendingSection.value = section;
  loading.value = true;

  router.reload({
    only: ['moodTrends'],
    data: { trendPeriod: period, trendSection: section },
    preserveUrl: true,
    onFinish: () => {
      loading.value = false;
      pendingPeriod.value = null;
      pendingSection.value = null;
      inflightController = null;
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
      <svg v-if="loading" class="w-4 h-4 animate-spin text-text-muted" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
      </svg>
    </div>

    <div class="p-5 flex flex-col flex-1 gap-4">
      <!-- Tabs -->
      <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
        <button v-for="tab in tabs" :key="tab" type="button" :disabled="loading" :class="[
          'flex-1 py-1.5 text-xs font-medium rounded-md transition-all duration-150 disabled:cursor-not-allowed',
          activePeriod === tab
            ? 'bg-white text-text-primary shadow-sm'
            : 'text-text-muted hover:text-text-secondary',
        ]" @click="applyFilter(tab, activeSection)">
          {{ tab }}
        </button>
      </div>

      <!-- Section Pills -->
      <div class="flex flex-wrap gap-1.5">
        <button v-for="s in props.data.sections" :key="s" type="button" :disabled="loading" :class="[
          'px-3 py-1 text-xs rounded-full font-medium transition-all duration-150 disabled:cursor-not-allowed',
          activeSection === s
            ? 'bg-sidebar text-white'
            : 'bg-pill-inactive-bg text-pill-inactive-text hover:bg-pill-inactive-bg-hover',
        ]" @click="applyFilter(activePeriod, s)">
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
