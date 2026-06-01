<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

defineProps<{
  items: BreadcrumbItem[];
}>();
</script>

<template>
  <nav aria-label="Breadcrumb">
    <ol class="flex items-center gap-1.5 text-sm text-text-muted">
      <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1.5">
        <ChevronRightIcon v-if="index > 0" class="h-3.5 w-3.5 shrink-0" />
        <Link
          v-if="item.href && index < items.length - 1"
          :href="item.href"
          class="hover:text-text-primary transition-colors"
        >
          {{ item.label }}
        </Link>
        <span v-else class="font-medium text-text-primary">{{ item.label }}</span>
      </li>
    </ol>
  </nav>
</template>
