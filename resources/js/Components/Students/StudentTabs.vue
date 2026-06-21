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

const TAB_ACTIVE: Record<StudentTab, string> = {
    All: 'bg-slate-600 text-white border-slate-600 shadow-sm',
    Pending: 'bg-slate-600 text-white border-slate-600 shadow-sm',
    Verified: 'bg-slate-600 text-white border-slate-600 shadow-sm',
    Suspended: 'bg-slate-600 text-white border-slate-600 shadow-sm',
};

const TAB_IDLE: Record<StudentTab, string> = {
    All: 'bg-slate-200 text-slate-600 border-slate-200 hover:bg-slate-200 hover:text-slate-800',
    Pending: 'bg-slate-200 text-slate-600 border-slate-200 hover:bg-slate-200 hover:text-slate-800',
    Verified: 'bg-slate-200 text-slate-600 border-slate-200 hover:bg-slate-200 hover:text-slate-800',
    Suspended: 'bg-slate-200 text-slate-600 border-slate-200 hover:bg-slate-200 hover:text-slate-800',
};
</script>

<template>
    <div class="flex items-center gap-2 flex-wrap">
        <button v-for="tab in STUDENT_TABS" :key="tab" @click="$emit('update:modelValue', tab)" :class="[
            'inline-flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold rounded-sm border transition-all',
            modelValue === tab ? TAB_ACTIVE[tab] : TAB_IDLE[tab],
        ]">
            <component :is="TAB_ICONS[tab]" class="h-5 w-5" />

            <span>{{ tab }}</span>

            <span :class="[
                'text-xs font-bold px-2 py-0.5 rounded-full',
                {
                    'bg-blue-100 text-blue-700': tab === 'All',
                    'bg-amber-100 text-amber-700': tab === 'Pending',
                    'bg-emerald-100 text-emerald-700': tab === 'Verified',
                    'bg-red-100 text-red-700': tab === 'Suspended',
                }
            ]">
                {{ counts[tab] }}
            </span>
        </button>
    </div>
</template>