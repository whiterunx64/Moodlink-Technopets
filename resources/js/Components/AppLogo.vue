<script setup lang="ts">
import AppLogoIcon from '@/Components/AppLogoIcon.vue';

defineProps<{ collapsed?: boolean }>();
</script>

<template>
  <div class="flex items-center gap-2">
    <AppLogoIcon />
    <h1 v-if="!collapsed" class="text-lg font-bold text-white tracking-wide">MoodLink</h1>
  </div>
</template>
