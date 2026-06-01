<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import AppHeader from '@/Components/AppHeader.vue';

defineProps<{
  title?: string;
}>();

const sidebar_open = ref(false);

function handleResize() {
  if (window.innerWidth >= 1024) sidebar_open.value = false;
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') sidebar_open.value = false;
}

onMounted(() => {
  window.addEventListener('resize', handleResize);
  window.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('keydown', handleEscape);
});
</script>

<template>
  <div class="flex min-h-screen bg-layout-bg">

    <!-- Mobile Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebar_open"
        class="fixed inset-0 z-20 bg-black/40 backdrop-blur-sm lg:hidden"
        @click="sidebar_open = false"
      />
    </Transition>

    <AppSidebar :open="sidebar_open" @close="sidebar_open = false" />

    <main class="flex flex-1 flex-col overflow-hidden min-w-0">
      <AppHeader :title="title ?? 'MoodLink'" @toggle-sidebar="sidebar_open = !sidebar_open" />

      <div class="flex-1 overflow-y-auto p-4 sm:p-6">
        <slot />
      </div>
    </main>

  </div>
</template>
