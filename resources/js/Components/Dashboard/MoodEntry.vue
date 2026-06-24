<script setup lang="ts">
import type { Mood } from '@/types';

withDefaults(defineProps<{
  name?: string;
  time?: string;
  message?: string | null;
  flagged?: boolean;
  mood?: Mood | null;
}>(), {
  flagged: false,
  mood: null,
});

const moodEmoji: Record<Mood, string> = {
  Excited: '🤩',
  Content: '😊',
  Stressed: '😰',
  Drained: '😩',
};
</script>

<template>
  <div class="group flex shrink-0 gap-3 p-4 rounded-xl bg-post-card-bg hover:bg-post-card-bg-hover transition-all duration-200">
    <!-- Avatar -->
    <div
      class="shrink-0 w-9 h-9 rounded-full bg-avatar-bg flex items-center justify-center text-sm font-bold text-text-primary select-none">
      {{ name?.charAt(0) ?? '?' }}
    </div>

    <div class="flex-1 min-w-0">
      <!-- Top row -->
      <div class="flex items-center justify-between gap-2 mb-1">
        <span class="text-sm font-semibold text-text-primary truncate">{{ name }}</span>
        <div class="flex items-center gap-2 shrink-0">
          <span v-if="mood" class="text-base leading-none">{{ moodEmoji[mood] }}</span>
          <span class="text-xs text-text-muted">{{ time }}</span>
        </div>
      </div>

      <!-- Message -->
      <p class="text-sm text-text-muted leading-relaxed line-clamp-2">{{ message }}</p>

      <!-- Badge -->
      <div class="mt-2 flex items-center gap-2">
        <span v-if="flagged"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-flagged-badge-bg text-flagged-badge-text text-xs font-medium">
          <i class="fas fa-flag text-[10px]" /> Flagged
        </span>
        <span v-else
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-safe-bg text-status-safe text-xs font-medium">
          <i class="fas fa-check text-[10px]" /> Safe
        </span>
      </div>
    </div>
  </div>
</template>
