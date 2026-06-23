<script setup lang="ts">
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import { VERIFICATION_BADGE, ACCOUNT_BADGE } from './studentBadges';
import type { Student } from '@/types';

defineProps<{ student: Student }>();

defineEmits<{
    open: [];
}>();
</script>

<template>
    <tr class="bg-table-row transition-colors hover:bg-table-row-hover">

        <!-- Student -->
        <td class="px-5 py-3.5 border-r border-table-grid">
            <div class="flex items-center gap-3 min-w-0">
                <Avatar :name="student.name" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-text-primary truncate">
                        {{ student.name }}
                    </p>
                    <p class="text-xs text-text-muted">
                        {{ student.section }}
                    </p>
                </div>
            </div>
        </td>

        <!-- Student ID -->
        <td class="px-4 py-3.5 border-r border-table-grid">
            <span class="text-sm text-text-secondary">
                {{ student.student_id }}
            </span>
        </td>

        <!-- Year Level -->
        <td class="px-4 py-3.5 border-r border-table-grid">
            <span class="text-sm text-text-secondary">
                {{ student.year_level }}
            </span>
        </td>

        <!-- Verification -->
        <td class="px-4 py-3.5 border-r border-table-grid">
            <Badge
                :label="VERIFICATION_BADGE[student.verification_status].label"
                :variant="VERIFICATION_BADGE[student.verification_status].variant"
                :icon="VERIFICATION_BADGE[student.verification_status].icon"
            />
        </td>

        <!-- Account Status -->
        <td class="px-4 py-3.5 border-r border-table-grid">
            <Badge
                :label="ACCOUNT_BADGE[student.account_status].label"
                :variant="ACCOUNT_BADGE[student.account_status].variant"
                :icon="ACCOUNT_BADGE[student.account_status].icon"
            />
        </td>

        <!-- Manage -->
        <td class="px-4 py-3.5 text-right">
            <button
                type="button"
                @click="$emit('open')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-sidebar px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-sidebar/90"
            >
                Manage
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
            </button>
        </td>

    </tr>
</template>
