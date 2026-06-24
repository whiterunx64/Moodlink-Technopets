<script setup lang="ts">
import type { StudentTab } from '@/types';
import { STUDENT_TABS } from '@/composables/useStudentFilters';
import {
    UsersIcon,
    ClockIcon,
    CheckCircleIcon,
    NoSymbolIcon,
} from '@heroicons/vue/24/outline';

defineProps<{
    modelValue: StudentTab;
    counts: Record<StudentTab, number>;
}>();

defineEmits<{
    'update:modelValue': [value: StudentTab]
}>();

const TAB_ICONS = {
    All: UsersIcon,
    Pending: ClockIcon,
    Verified: CheckCircleIcon,
    Suspended: NoSymbolIcon,
};

const TAB_COUNT: Record<StudentTab, string> = {
    All:       'bg-white/25 text-white',
    Pending:   'bg-white/25 text-white',
    Verified:  'bg-white/25 text-white',
    Suspended: 'bg-white/25 text-white',
};

const TAB_COUNT_IDLE: Record<StudentTab, string> = {
    All:       'bg-gray-100 text-gray-600',
    Pending:   'bg-amber-100 text-amber-700',
    Verified:  'bg-green-100 text-green-700',
    Suspended: 'bg-red-100 text-red-600',
};
</script>

<template>
    <div class="flex items-center gap-1.5 flex-wrap sm:gap-2">
        <button
            v-for="tab in STUDENT_TABS"
            :key="tab"
            type="button"
            @click="$emit('update:modelValue', tab)"
            :class="[
                'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-all duration-150 sm:gap-2 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm',
                modelValue === tab
                    ? 'bg-sidebar border-sidebar text-white shadow-sm'
                    : 'border-gray-200 bg-white text-gray-600 hover:border-sidebar/40 hover:bg-sidebar/5 hover:text-sidebar',
            ]"
        >
            <component :is="TAB_ICONS[tab]" class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" />
            <span>{{ tab }}</span>
            <span :class="[
                'rounded-full px-1.5 py-0.5 text-[10px] font-bold sm:text-xs',
                modelValue === tab ? TAB_COUNT[tab] : TAB_COUNT_IDLE[tab],
            ]">
                {{ counts[tab] }}
            </span>
        </button>
    </div>
</template>
