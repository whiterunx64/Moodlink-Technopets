<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeftStartOnRectangleIcon } from '@heroicons/vue/24/outline';
import type { PageProps } from '@/types';

const user = computed(() => usePage<PageProps>().props.auth.user);

const initials = computed(() =>
  user.value?.name
    ?.split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2) ?? '?'
);
</script>

<template>
  <div
    v-if="user"
    class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 transition-colors cursor-pointer group"
  >
    <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20 text-sm font-bold text-white">
      <img v-if="user.avatar" :src="user.avatar" alt="Avatar" class="h-full w-full object-cover" />
      <span v-else>{{ initials }}</span>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
      <p class="text-xs text-white/50 truncate">{{ user.email }}</p>
    </div>
    <Link
      href="/logout"
      method="post"
      as="button"
      class="p-0 bg-transparent border-0"
    >
      <ArrowLeftStartOnRectangleIcon class="h-4 w-4 text-white/30 group-hover:text-white/70 transition-colors shrink-0" />
    </Link>
  </div>
</template>
