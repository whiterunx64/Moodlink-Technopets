<script setup lang="ts">
export interface FilterTab {
    label: string;
    value: string;
    badge?: number;
    badgeInactiveClass?: string;
}

defineProps<{
    modelValue: string;
    tabs: FilterTab[];
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div class="flex items-center gap-1 bg-bg-surface border border-border-light rounded-xl p-1 w-fit">

        <button v-for="tab in tabs" :key="tab.value" @click="$emit('update:modelValue', tab.value)" :class="[
            'px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150 focus:outline-none focus:ring-0',

            /* active tab */
            modelValue === tab.value
                ? 'bg-filter-active-bg text-filter-active-text shadow-sm'

                /* inactive tab */
                : 'text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg',
        ]">
            {{ tab.label }}

            <span v-if="tab.badge !== undefined" :class="[
                'ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full',

                /* active badge */
                modelValue === tab.value
                    ? 'bg-filter-badge-active-bg text-filter-badge-active-text'

                    /* inactive badge */
                    : (tab.badgeInactiveClass ?? 'bg-filter-badge-bg text-filter-badge-text'),
            ]">
                {{ tab.badge }}
            </span>
        </button>
    </div>
</template>