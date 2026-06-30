<script setup lang="ts">
import {
  Squares2X2Icon,
  UserGroupIcon,
  DocumentTextIcon,
  ChartBarIcon,
  CalendarDaysIcon,
  UserCircleIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import NavMain, { type NavItem } from '@/Components/NavMain.vue';
import NavUser from '@/Components/NavUser.vue';
import NavFooter from '@/Components/NavFooter.vue';

defineProps<{
  open: boolean;
  collapsed: boolean;
}>();

const emit = defineEmits<{
  close: [];
  'toggle-collapsed': [];
}>();

const nav_items: NavItem[] = [
  { label: 'Dashboard',       icon: Squares2X2Icon,   href: route('dashboard') },
  { label: 'Users', icon: UserGroupIcon, href: route('student-accounts.index') },
  { label: 'Post Management',  icon: DocumentTextIcon, href: route('posts.index') },
  { label: 'Summary Reports', icon: ChartBarIcon,     href: route('reports.index') },
  { label: 'Appointments',    icon: CalendarDaysIcon, href: route('appointments.index') },
  { label: 'Account',         icon: UserCircleIcon,   href: route('profile.settings') },
];
</script>

<template>
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-30 flex flex-col bg-sidebar overflow-hidden',
      'transition-all duration-300 ease-in-out',
      collapsed ? 'w-16' : 'w-64',
      open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <!-- Logo -->
    <div :class="['flex items-center border-b border-sidebar-border', collapsed ? 'justify-center px-2 py-5' : 'justify-between px-4 py-5']">
      <AppLogo :collapsed="collapsed" />
      <button
        v-if="!collapsed"
        type="button"
        class="p-1 text-white/60 hover:text-white transition-colors lg:hidden"
        @click="emit('close')"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
      <button
        v-if="!collapsed"
        type="button"
        class="hidden lg:flex p-1 text-white/60 hover:text-white transition-colors"
        @click="emit('toggle-collapsed')"
      >
        <ChevronLeftIcon class="h-5 w-5" />
      </button>
    </div>

    <!-- Nav -->
    <div class="flex-1 px-2 py-4 overflow-y-auto">
      <NavMain :items="nav_items" :collapsed="collapsed" @navigate="emit('close')" />
    </div>

    <!-- Expand toggle (desktop, collapsed state only) -->
    <div v-if="collapsed" class="hidden lg:flex justify-center pb-2">
      <button
        type="button"
        class="p-1.5 text-white/60 hover:text-white transition-colors rounded-md hover:bg-white/10"
        @click="emit('toggle-collapsed')"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </button>
    </div>

    <!-- Footer -->
    <NavFooter>
      <NavUser :collapsed="collapsed" />
    </NavFooter>
  </aside>
</template>
