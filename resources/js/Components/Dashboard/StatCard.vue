<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

type PillVariant = 'green' | 'red' | 'orange' | 'neutral';

export type BreakdownPill = {
    type: 'pill';
    label: string;
    count: number;
    variant: PillVariant;
};
export type BreakdownBadge = {
    type: 'badge';
    label: string;
    value: string | null;
    color: string;
};
export type BreakdownRate = {
    type: 'rate';
    label: string;
    value: number;
    dotColor: string;
};
export type BreakdownItem = BreakdownPill | BreakdownBadge | BreakdownRate;

withDefaults(
    defineProps<{
        label: string;
        value: number;
        icon?: Component;
        iconBg?: string;
        iconColor?: string;
        cardBg?: string;
        borderColor?: string;
        hoverRing?: string;
        href?: string;
        rows?: BreakdownItem[];
    }>(),
    {
        cardBg: 'bg-white',
        borderColor: 'border-border-light',
        hoverRing: '',
        href: undefined,
        rows: () => [],
    },
);

const pillClass: Record<PillVariant, string> = {
    green: 'bg-status-safe-bg text-status-safe',
    red: 'bg-status-flagged-bg text-status-flagged',
    orange: 'bg-orange-100 text-orange-700',
    neutral: 'bg-gray-100 text-gray-500',
};
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="[
            'block border p-4 shadow-sm transition-all duration-200',
            href
                ? 'cursor-pointer hover:-translate-y-1 hover:shadow-xl'
                : 'cursor-default',
            hoverRing,
            cardBg,
            borderColor,
        ]"
    >
        <!-- Icon + label -->
        <div class="flex justify-between">
            <div class="mb-3 flex items-center gap-2.5">
                <div
                    v-if="icon"
                    :class="[
                        'flex h-9 w-9 shrink-0 items-center justify-center',
                        iconBg,
                    ]"
                >
                    <component :is="icon" :class="['h-4 w-4', iconColor]" />
                </div>
                <p
                    class="text-text-muted text-xs font-semibold tracking-widest uppercase"
                >
                    {{ label }}
                </p>
            </div>

            <!-- Count -->
            <p
                class="text-text-primary mb-4 text-4xl font-extrabold tracking-tight"
            >
                {{ value }}
            </p>
        </div>

        <!-- Breakdown rows -->
        <div :class="['space-y-2 border-t pt-3', borderColor]">
            <div
                v-for="(row, i) in rows"
                :key="i"
                class="flex items-center justify-between"
            >
                <span class="text-text-muted text-xs font-medium">{{
                    row.label
                }}</span>

                <span
                    v-if="row.type === 'pill'"
                    :class="[
                        'px-2.5 py-0.5 text-xs font-semibold',
                        pillClass[row.variant],
                    ]"
                >
                    {{ row.count }}
                </span>

                <template v-else-if="row.type === 'badge'">
                    <span
                        v-if="row.value"
                        :class="[
                            'px-2.5 py-0.5 text-xs font-semibold',
                            row.color,
                        ]"
                    >
                        {{ row.value }}
                    </span>
                    <span v-else class="text-xs text-gray-300">—</span>
                </template>

                <span
                    v-else-if="row.type === 'rate'"
                    class="text-text-secondary flex items-center gap-1.5 text-xs font-semibold"
                >
                    <span :class="['h-2 w-2 shrink-0', row.dotColor]" />
                    {{ row.value }}%
                </span>
            </div>
        </div>
    </component>
</template>
