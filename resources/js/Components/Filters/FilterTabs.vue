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
    <div class="min-w-0 overflow-x-auto">
        <div
            class="bg-bg-surface border-border-light flex w-fit min-w-max items-center gap-1 border p-1"
        >
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="$emit('update:modelValue', tab.value)"
                :class="[
                    'cursor-pointer p-2 px-3 text-xs font-medium transition-all duration-150 focus:ring-0 focus:outline-none sm:px-4',

                    /* active tab */
                    modelValue === tab.value
                        ? 'bg-filter-active-bg text-filter-active-text shadow-sm'
                        : /* inactive tab */
                          'text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg',
                ]"
            >
                {{ tab.label }}

                <span
                    v-if="tab.badge !== undefined"
                    :class="[
                        'ml-1.5 px-1.5 py-0.5 text-xs font-bold',

                        /* active badge */
                        modelValue === tab.value
                            ? 'bg-filter-badge-active-bg text-filter-badge-active-text'
                            : /* inactive badge */
                              (tab.badgeInactiveClass ??
                              'bg-filter-badge-bg text-filter-badge-text'),
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </div>
    </div>
</template>
