<script setup lang="ts">
import { STUDENT_TABS } from '@/composables/useStudentFilters';
import type { StudentTab } from '@/types';
import {
    CheckCircleIcon,
    ClockIcon,
    NoSymbolIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

defineProps<{
    modelValue: StudentTab;
    counts: Record<StudentTab, number>;
}>();

defineEmits<{
    'update:modelValue': [value: StudentTab];
}>();

const TAB_ICONS: Record<StudentTab, typeof UsersIcon> = {
    all: UsersIcon,
    pending: ClockIcon,
    verified: CheckCircleIcon,
    suspended: NoSymbolIcon,
};

const TAB_COUNT: Record<StudentTab, string> = {
    all: 'bg-white/25 text-white',
    pending: 'bg-white/25 text-white',
    verified: 'bg-white/25 text-white',
    suspended: 'bg-white/25 text-white',
};

const TAB_COUNT_IDLE: Record<StudentTab, string> = {
    all: 'bg-gray-100 text-gray-600',
    pending: 'bg-amber-100 text-amber-700',
    verified: 'bg-green-100 text-green-700',
    suspended: 'bg-red-100 text-red-600',
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
        <button
            v-for="tab in STUDENT_TABS"
            :key="tab.value"
            type="button"
            @click="$emit('update:modelValue', tab.value)"
            :class="[
                'inline-flex cursor-pointer items-center gap-1.5 border px-2.5 py-1.5 text-xs font-semibold transition-all duration-150 sm:gap-2 sm:px-4 sm:py-2.5 sm:text-sm',
                modelValue === tab.value
                    ? 'bg-sidebar border-sidebar text-white shadow-sm'
                    : 'hover:border-sidebar/40 hover:bg-sidebar/5 hover:text-sidebar border-gray-200 bg-white text-gray-600',
            ]"
        >
            <component
                :is="TAB_ICONS[tab.value]"
                class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4"
            />
            <span>{{ tab.label }}</span>
            <span
                :class="[
                    'px-1.5 py-0.5 text-[10px] font-bold sm:text-xs',
                    modelValue === tab.value
                        ? TAB_COUNT[tab.value]
                        : TAB_COUNT_IDLE[tab.value],
                ]"
            >
                {{ counts[tab.value] ?? 0 }}
            </span>
        </button>
    </div>
</template>
