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
    // Compare only the path portion since route() returns a full URL
    try {
        const path = new URL(href).pathname;
        return current_page.url.startsWith(path);
    } catch {
        return current_page.url.startsWith(href);
    }
}
</script>

<template>
    <nav role="navigation" class="space-y-0.5">
        <p
            v-if="!collapsed"
            class="mb-2 px-3 text-[10px] font-semibold tracking-widest text-white/30 uppercase"
        >
            Menu
        </p>
        <Link
            v-for="item in items"
            :key="item.label"
            :href="item.href"
            :title="collapsed ? item.label : undefined"
            :class="[
                'flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
                isActive(item.href)
                    ? 'border-l-4 border-white bg-white/15 pl-2 text-white shadow-sm'
                    : 'border-l-4 border-transparent pl-2 text-white/65 hover:bg-white/10 hover:text-white',
            ]"
            @click="emit('navigate')"
        >
            <span
                v-if="isActive(item.href) && !collapsed"
                class="absolute top-1/2 left-0 h-5 w-0.5 -translate-y-1/2 rounded-full bg-white"
            />
            <component
                :is="item.icon"
                class="h-5 w-5 shrink-0"
                aria-hidden="true"
            />
            <span v-if="!collapsed">{{ item.label }}</span>
        </Link>
    </nav>
</template>
