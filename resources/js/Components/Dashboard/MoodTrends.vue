<script setup lang="ts">
import { ref } from 'vue';

const activeTab = ref<'Today' | 'Weekly' | 'Monthly'>('Today');
const activeSection = ref('All');

const tabs = ['Today', 'Weekly', 'Monthly'] as const;
const sections = ['All', 'DW31', 'DX30', 'DX31A'];

interface DistributionItem {
  label: string;
  pct: number;
  color: string;
}

const distribution: DistributionItem[] = [
  { label: 'Happy', pct: 42, color: 'bg-green-400' },
  { label: 'Neutral', pct: 28, color: 'bg-blue-400' },
  { label: 'Anxious', pct: 18, color: 'bg-yellow-400' },
  { label: 'Sad', pct: 12, color: 'bg-red-400' },
];
</script>

<template>
  <div class="bg-white rounded-2xl border border-border-light shadow-sm flex flex-col h-full">
    <!-- Header -->
    <div class="px-5 pt-5 pb-4 border-b border-border-light">
      <h3 class="text-base font-semibold text-text-primary">Mood Trends</h3>
      <p class="text-xs text-text-muted mt-0.5">Emotional distribution overview</p>
    </div>

    <div class="p-5 flex flex-col flex-1 gap-4">
      <!-- Tabs -->
      <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
        <button v-for="tab in tabs" :key="tab" type="button" :class="[
          'flex-1 py-1.5 text-xs font-medium rounded-md transition-all duration-150',
          activeTab === tab
            ? 'bg-white text-text-primary shadow-sm'
            : 'text-text-muted hover:text-text-secondary',
        ]" @click="activeTab = tab">
          {{ tab }}
        </button>
      </div>

      <!-- Section Pills -->
      <div class="flex flex-wrap gap-1.5">
        <button v-for="s in sections" :key="s" type="button" :class="[
          'px-3 py-1 text-xs rounded-full font-medium transition-all duration-150',
          activeSection === s
            ? 'bg-sidebar text-white'
            : 'bg-pill-inactive-bg text-pill-inactive-text hover:bg-pill-inactive-bg-hover',
        ]" @click="activeSection = s">
          {{ s }}
        </button>
      </div>

      <!-- Mood Distribution Bars -->
      <div class="flex-1 flex flex-col justify-center gap-3">
        <div v-for="item in distribution" :key="item.label" class="space-y-1">
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

      <!-- Legend -->
      <div class="grid grid-cols-2 gap-2 pt-2 border-t border-border-light">
        <div v-for="item in distribution" :key="item.label" class="flex items-center gap-1.5">
          <div :class="['w-2.5 h-2.5 rounded-full shrink-0', item.color]" />
          <span class="text-xs text-text-muted">{{ item.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
