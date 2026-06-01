<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import type { Component } from 'vue';

export interface NavItem {
  label: string;
  icon: Component;
  href: string;
}

defineProps<{
  items: NavItem[];
}>();

const emit = defineEmits<{
  navigate: [];
}>();

const current_page = usePage();

function isActive(href: string) {
  return current_page.url.startsWith(href);
}
</script>

<template>
  <nav role="navigation" class="space-y-0.5">
    <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-white/30">Menu</p>
    <Link
      v-for="item in items"
      :key="item.label"
      :href="item.href"
      :class="[
        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
        isActive(item.href)
          ? 'bg-white/15 text-white shadow-sm'
          : 'text-white/65 hover:bg-white/10 hover:text-white',
      ]"
      @click="emit('navigate')"
    >
      <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
      {{ item.label }}
    </Link>
  </nav>
</template>
