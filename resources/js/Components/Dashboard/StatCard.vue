<script setup lang="ts">
import type { Component } from 'vue';

const colorClass = {
    orange: 'bg-card-orange',
    green: 'bg-card-green',
    red: 'bg-card-red',
    blue: 'bg-card-blue',
} as const;

withDefaults(defineProps<{
    title: string;
    value: number | string;
    icon?: Component;
    color?: 'orange' | 'green' | 'red' | 'blue';
    change?: string | null;
    changeUp?: boolean;
}>(), {
    color: 'blue',
    change: null,
    changeUp: true,
});
</script>

<template>
    <div :class="[
        'relative overflow-hidden rounded-2xl p-5 text-white',
        colorClass[color ?? 'blue']
    ]">
        <div class="pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />

        <div class="relative flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium uppercase tracking-wide text-white/75">
                    {{ title }}
                </p>

                <p class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">
                    {{ value }}
                </p>

                <p v-if="change" class="mt-1 text-xs text-white/70">
                    <span :class="changeUp ? 'text-green-200' : 'text-red-200'">
                        {{ changeUp ? '▲' : '▼' }} {{ change }}
                    </span>
                    vs yesterday
                </p>
            </div>

            <div v-if="icon" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
                <component :is="icon" class="h-6 w-6 text-white" />
            </div>
        </div>
    </div>
</template>
