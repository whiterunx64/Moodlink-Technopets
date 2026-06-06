<script setup lang="ts">
import type { StudentTab } from '@/types';
import { STUDENT_TABS } from '@/composables/useStudentFilters';

defineProps<{
    modelValue: StudentTab;
    counts: Record<StudentTab, number>;
}>();

defineEmits<{ 'update:modelValue': [value: StudentTab] }>();
</script>

<template>
    <div class="flex items-center gap-2 flex-wrap">
        <button
            v-for="tab in STUDENT_TABS"
            :key="tab"
            @click="$emit('update:modelValue', tab)"
            :class="[
                'inline-flex items-center gap-2 px-4 py-1.5 text-xs font-semibold rounded-full border transition-colors',
                modelValue === tab
                    ? 'bg-sidebar text-white border-sidebar'
                    : 'bg-white text-text-muted border-border-light hover:border-gray-300 hover:text-text-secondary',
            ]"
        >
            {{ tab }}
            <span
                :class="[
                    'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                    modelValue === tab ? 'bg-white/20 text-white' : 'bg-gray-100 text-text-muted',
                ]"
            >
                {{ counts[tab] }}
            </span>
        </button>
    </div>
</template>
