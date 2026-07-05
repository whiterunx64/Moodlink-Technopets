<script setup lang="ts">
import AppLogo from '@/Components/AppLogo.vue';
import NavFooter from '@/Components/NavFooter.vue';
import NavMain, { type NavItem } from '@/Components/NavMain.vue';
import NavUser from '@/Components/NavUser.vue';
import {
    CalendarDaysIcon,
    ChartBarIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    Squares2X2Icon,
    UserCircleIcon,
    UserGroupIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

defineProps<{
    open: boolean;
    collapsed: boolean;
}>();

const emit = defineEmits<{
    close: [];
    'toggle-collapsed': [];
}>();

const nav_items: NavItem[] = [
    { label: 'Dashboard', icon: Squares2X2Icon, href: route('dashboard') },
    {
        label: 'Users',
        icon: UserGroupIcon,
        href: route('student-accounts.index'),
    },
    {
        label: 'Post Management',
        icon: DocumentTextIcon,
        href: route('posts.index'),
    },
    {
        label: 'Summary Reports',
        icon: ChartBarIcon,
        href: route('reports.index'),
    },
    {
        label: 'Appointments',
        icon: CalendarDaysIcon,
        href: route('appointments.index'),
    },
    { label: 'Account', icon: UserCircleIcon, href: route('profile.settings') },
];
</script>

<template>
    <aside
        :class="[
            'bg-sidebar fixed inset-y-0 left-0 z-30 flex flex-col overflow-hidden',
            'transition-all duration-300 ease-in-out',
            collapsed ? 'w-16' : 'w-64',
            open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
    >
        <!-- Logo -->
        <div
            :class="[
                'border-sidebar-border flex items-center border-b',
                collapsed
                    ? 'justify-center px-2 py-5'
                    : 'justify-between px-4 py-5',
            ]"
        >
            <AppLogo :collapsed="collapsed" />
            <button
                v-if="!collapsed"
                type="button"
                class="cursor-pointer p-1 text-white/60 transition-colors hover:text-white lg:hidden"
                @click="emit('close')"
            >
                <XMarkIcon class="h-5 w-5" />
            </button>
            <button
                v-if="!collapsed"
                type="button"
                class="hidden p-1 text-white/60 transition-colors hover:text-white lg:flex"
                @click="emit('toggle-collapsed')"
            >
                <ChevronLeftIcon class="h-5 w-5 cursor-pointer" />
            </button>
        </div>

        <!-- Nav -->
        <div class="flex-1 overflow-y-auto px-2 py-4">
            <NavMain
                :items="nav_items"
                :collapsed="collapsed"
                @navigate="emit('close')"
            />
        </div>

        <!-- Expand toggle (desktop, collapsed state only) -->
        <div v-if="collapsed" class="hidden justify-center pb-2 lg:flex">
            <button
                type="button"
                class="rounded-md p-1.5 text-white/60 transition-colors hover:bg-white/10 hover:text-white"
                @click="emit('toggle-collapsed')"
            >
                <ChevronRightIcon class="h-4 w-4 cursor-pointer" />
            </button>
        </div>

        <!-- Footer -->
        <NavFooter>
            <NavUser :collapsed="collapsed" />
        </NavFooter>
    </aside>
</template>
