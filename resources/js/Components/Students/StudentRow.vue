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
        <td class="px-3 py-2.5 border-r border-table-grid sm:px-4 sm:py-3 lg:px-5 lg:py-3.5">
            <div class="flex items-center gap-2 min-w-0 sm:gap-3">
                <Avatar :name="student.name" />
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-text-primary truncate sm:text-sm">
                        {{ student.name }}
                    </p>
                    <p class="text-[11px] text-text-muted truncate sm:text-xs">
                        {{ student.section }}
                    </p>
                </div>
            </div>
        </td>

        <!-- Student ID -->
        <td class="px-2.5 py-2.5 border-r border-table-grid sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <span class="text-xs text-text-secondary sm:text-sm">
                {{ student.student_id }}
            </span>
        </td>

        <!-- Year Level -->
        <td class="px-2.5 py-2.5 border-r border-table-grid sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <span class="text-xs text-text-secondary sm:text-sm">
                {{ student.year_level }}
            </span>
        </td>

        <!-- Verification -->
        <td class="px-2.5 py-2.5 border-r border-table-grid sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <Badge
                :label="VERIFICATION_BADGE[student.verification_status].label"
                :variant="VERIFICATION_BADGE[student.verification_status].variant"
                :icon="VERIFICATION_BADGE[student.verification_status].icon"
            />
        </td>

        <!-- Account Status -->
        <td class="px-2.5 py-2.5 border-r border-table-grid sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <Badge
                :label="ACCOUNT_BADGE[student.account_status].label"
                :variant="ACCOUNT_BADGE[student.account_status].variant"
                :icon="ACCOUNT_BADGE[student.account_status].icon"
            />
        </td>

        <!-- Manage -->
        <td class="px-2.5 py-2.5 text-right sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <button
                type="button"
                @click="$emit('open')"
                class="inline-flex items-center gap-1 rounded-md bg-sidebar px-2.5 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-sidebar/90 sm:gap-1.5 sm:rounded-lg sm:px-3 sm:text-xs"
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
