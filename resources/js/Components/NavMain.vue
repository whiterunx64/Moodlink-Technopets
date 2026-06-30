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
  collapsed?: boolean;
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
    <p v-if="!collapsed" class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-white/30">Menu</p>
    <Link
      v-for="item in items"
      :key="item.label"
      :href="item.href"
      :title="collapsed ? item.label : undefined"
      :class="[
        'relative flex items-center rounded-lg py-2.5 text-sm font-medium transition-all duration-150',
        collapsed ? 'justify-center px-2' : 'gap-3 px-3',
        isActive(item.href)
          ? 'bg-white/15 text-white shadow-sm'
          : 'text-white/65 hover:bg-white/10 hover:text-white',
      ]"
      @click="emit('navigate')"
    >
      <span
        v-if="isActive(item.href) && !collapsed"
        class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-0.5 rounded-full bg-white"
      />
      <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
      <span v-if="!collapsed">{{ item.label }}</span>
    </Link>
  </nav>
</template>
