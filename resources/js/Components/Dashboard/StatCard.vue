<script setup lang="ts">
const colorClass = {
  orange: 'bg-card-orange',
  green: 'bg-card-green',
  red: 'bg-card-red',
  blue: 'bg-card-blue',
} as const;

const props = withDefaults(defineProps<{
  title: string;
  value: number | string;
  icon: string;
  color?: 'orange' | 'green' | 'red' | 'blue';
  change?: string | null;
  changeUp?: boolean;
}>(), {
  color: 'blue',
  change: null,
  changeUp: true,
});
</script>

<template>
  <div :class="['rounded-2xl p-5 text-white relative overflow-hidden', colorClass[props.color]]">
    <div class="absolute -top-4 -right-4 w-24 h-24 rounded-full bg-white/10 pointer-events-none" />

    <div class="relative flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <p class="text-xs font-medium text-white/75 uppercase tracking-wide truncate">{{ title }}</p>
        <p class="mt-2 text-4xl font-extrabold tracking-tight">{{ value }}</p>
        <p v-if="change" class="mt-1 text-xs text-white/70">
          <span :class="changeUp ? 'text-green-200' : 'text-red-200'">
            {{ changeUp ? '▲' : '▼' }} {{ change }}
          </span>
          vs yesterday
        </p>
      </div>
      <div v-if="icon" class="shrink-0 w-11 h-11 flex items-center justify-center rounded-xl bg-white/15">
        <i :class="[icon, 'text-xl text-white']" />
      </div>
    </div>
  </div>
</template>