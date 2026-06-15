<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppSidebar from '@/Components/AppSidebar.vue';
import AppHeader from '@/Components/AppHeader.vue';
import { useToast } from '@/composables/useToast';

defineProps<{
  title?: string;
}>();

const sidebar_open = ref(false);
const page = usePage();
const { add: addToast } = useToast();

const flash = () => page.props.flash as { error?: string; success?: string };

watch(
  () => flash()?.error,
  (error) => {
    if (error) addToast({ type: 'error', message: error });
  },
);

watch(
  () => flash()?.success,
  (success) => {
    if (success) addToast({ type: 'success', message: success });
  },
);

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
  <div class="min-h-screen bg-layout-bg">

    <!-- Keeps backdrop-filter GPU layer warm; prevents first-open blur delay on modals -->
    <div class="fixed w-0 h-0 backdrop-blur-sm pointer-events-none" aria-hidden="true" />

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

    <main class="flex flex-col min-h-screen lg:pl-64">
      <AppHeader :title="title ?? 'MoodLink'" @toggle-sidebar="sidebar_open = !sidebar_open" />

      <div class="flex-1 p-4 sm:p-6">
        <slot />
      </div>
    </main>

  </div>
</template>
