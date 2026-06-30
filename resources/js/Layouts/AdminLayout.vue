<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppSidebar from '@/Components/AppSidebar.vue';
import AppHeader from '@/Components/AppHeader.vue';
import { useToast } from '@/composables/useToast';

defineProps<{
  title?: string;
}>();

const sidebar_open = ref(false);
const sidebar_collapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true');
const is_desktop = ref(window.innerWidth >= 1024);
const page = usePage();
const { add: addToast } = useToast();

const effective_collapsed = computed(() => sidebar_collapsed.value && is_desktop.value);

watch(sidebar_collapsed, (val) => {
  localStorage.setItem('sidebar_collapsed', String(val));
});

watch(
  () => page.props.flash,
  (flash) => {
    const { error, success } = (flash ?? {}) as { error?: string; success?: string };
    if (error) addToast({ type: 'error', message: error });
    if (success) addToast({ type: 'success', message: success });
  },
  { deep: true },
);

function handleResize() {
  is_desktop.value = window.innerWidth >= 1024;
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
  <div class="min-h-screen bg-layout-bg overflow-x-hidden">

    <!-- Keeps backdrop-filter GPU layer warm; prevents first-open blur delay on modals -->
    <div class="fixed w-0 h-0 backdrop-blur-sm pointer-events-none" aria-hidden="true" />

    <!-- Mobile Overlay -->
    <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300" leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="sidebar_open" class="fixed inset-0 z-20 bg-black/40 backdrop-blur-sm lg:hidden"
        @click="sidebar_open = false" />
    </Transition>

    <AppSidebar
      :open="sidebar_open"
      :collapsed="effective_collapsed"
      @close="sidebar_open = false"
      @toggle-collapsed="sidebar_collapsed = !sidebar_collapsed"
    />

    <main :class="['flex flex-col min-h-screen transition-all duration-300', sidebar_collapsed ? 'lg:pl-16' : 'lg:pl-64']">
      <AppHeader :title="title ?? 'MoodLink'" @toggle-sidebar="sidebar_open = !sidebar_open" />

      <div class="flex-1 p-4 sm:p-6">
        <slot />
      </div>
    </main>

  </div>
</template>
