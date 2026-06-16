<script setup lang="ts">
import {
  Squares2X2Icon,
  UserGroupIcon,
  DocumentTextIcon,
  ChartBarIcon,
  CalendarDaysIcon,
  UserCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';
import NavMain, { type NavItem } from '@/Components/NavMain.vue';
import NavUser from '@/Components/NavUser.vue';
import NavFooter from '@/Components/NavFooter.vue';

defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

const nav_items: NavItem[] = [
  { label: 'Dashboard',       icon: Squares2X2Icon,   href: route('dashboard') },
  { label: 'Users', icon: UserGroupIcon, href: route('user-accounts.index') }, 
  { label: 'Post Management',  icon: DocumentTextIcon, href: route('post-management.index') },
  { label: 'Summary Reports', icon: ChartBarIcon,     href: route('summary-reports.index') },
  { label: 'Appointments',    icon: CalendarDaysIcon, href: route('appointments.index') },
  { label: 'Account',         icon: UserCircleIcon,   href: route('profile.settings') },
];
</script>

<template>
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-30 flex w-64 shrink-0 flex-col bg-sidebar',
      'transition-transform duration-300 ease-in-out',
      open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center justify-between px-6 py-5 border-b border-sidebar-border">
      <AppLogo />
      <button
        type="button"
        class="p-1 text-white/60 hover:text-white transition-colors lg:hidden"
        @click="emit('close')"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>

    <!-- Nav -->
    <div class="flex-1 px-3 py-4 overflow-y-auto">
      <NavMain :items="nav_items" @navigate="emit('close')" />
    </div>

    <!-- Footer -->
    <NavFooter>
      <NavUser />
    </NavFooter>
  </aside>
</template>
