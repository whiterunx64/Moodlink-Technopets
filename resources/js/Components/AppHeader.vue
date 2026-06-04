<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Bars3Icon } from '@heroicons/vue/24/outline';

defineProps<{
  title: string;
}>();

const emit = defineEmits<{
  'toggle-sidebar': [];
}>();

const clock = ref('');
let clock_interval: ReturnType<typeof setInterval> | null = null;

function updateClock() {
  clock.value = new Date().toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

onMounted(() => {
  updateClock();
  clock_interval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  if (clock_interval) clearInterval(clock_interval);
});
</script>

<template>
  <header class="flex items-center gap-4 border-b border-header-border bg-header-bg px-6 py-4 shrink-0 sticky top-0 z-10">
    <button
      type="button"
      class="p-1 text-header-icon hover:text-header-icon-hover transition-colors lg:hidden"
      @click="emit('toggle-sidebar')"
    >
      <Bars3Icon class="h-6 w-6" />
    </button>

    <div class="flex-1 min-w-0">
      <h2 class="text-xl font-bold text-text-primary truncate">{{ title }}</h2>
      <p class="text-xs text-text-muted mt-0.5">{{ clock }}</p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <div class="w-8 h-8 rounded-full bg-sidebar/10 flex items-center justify-center">
        <span class="text-sidebar text-sm font-bold">A</span>
      </div>
    </div>
  </header>
</template>
