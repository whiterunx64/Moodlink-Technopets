<script setup lang="ts">
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import type { Student } from '@/types';
import { ACCOUNT_BADGE, VERIFICATION_BADGE } from './studentBadges';

defineProps<{ student: Student }>();
defineEmits<{ open: [] }>();
</script>

<template>
    <tr class="bg-table-row hover:bg-table-row-hover transition-colors">
        <!-- Student -->
        <td
            class="border-table-grid border-r px-3 py-2.5 sm:px-4 sm:py-3 lg:px-5 lg:py-3.5"
        >
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <Avatar :name="student.name" />
                <div class="min-w-0">
                    <p
                        class="text-text-primary truncate text-xs font-semibold sm:text-sm"
                    >
                        {{ student.name }}
                    </p>
                    <p class="text-text-muted truncate text-[11px] sm:text-xs">
                        {{ student.program }}
                    </p>
                </div>
            </div>
        </td>

        <!-- Student ID -->
        <td
            class="border-table-grid border-r px-2.5 py-2.5 sm:px-3 sm:py-3 lg:px-4 lg:py-3.5"
        >
            <span class="text-text-secondary text-xs sm:text-sm">
                {{ student.student_id }}
            </span>
        </td>

        <!-- Year Level -->
        <td
            class="border-table-grid border-r px-2.5 py-2.5 sm:px-3 sm:py-3 lg:px-4 lg:py-3.5"
        >
            <span class="text-text-secondary text-xs sm:text-sm">
                {{ student.year_level }}
            </span>
        </td>

        <!-- Verification -->
        <td
            class="border-table-grid border-r px-2.5 py-2.5 sm:px-3 sm:py-3 lg:px-4 lg:py-3.5"
        >
            <Badge
                :label="VERIFICATION_BADGE[student.verification_status].label"
                :variant="
                    VERIFICATION_BADGE[student.verification_status].variant
                "
                :icon="VERIFICATION_BADGE[student.verification_status].icon"
            />
        </td>

        <!-- Account Status -->
        <td
            class="border-table-grid border-r px-2.5 py-2.5 sm:px-3 sm:py-3 lg:px-4 lg:py-3.5"
        >
            <Badge
                v-if="ACCOUNT_BADGE[student.account_status]"
                :label="ACCOUNT_BADGE[student.account_status]!.label"
                :variant="ACCOUNT_BADGE[student.account_status]!.variant"
                :icon="ACCOUNT_BADGE[student.account_status]!.icon"
            />
            <span v-else class="text-xs text-gray-300">—</span>
        </td>

        <!-- Manage -->
        <td class="px-2.5 py-2.5 text-right sm:px-3 sm:py-3 lg:px-4 lg:py-3.5">
            <button
                type="button"
                @click="$emit('open')"
                class="bg-sidebar hover:bg-sidebar/90 inline-flex cursor-pointer items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold text-white transition-colors sm:gap-1.5 sm:px-3 sm:text-xs"
            >
                Manage
                <svg
                    xmlns="http://www.ffw3.org/2000/svg"
                    class="h-3 w-3 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
            </button>
        </td>
    </tr>
</template>
