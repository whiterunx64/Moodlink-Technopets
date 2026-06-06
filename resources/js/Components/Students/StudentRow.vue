<script setup lang="ts">
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import StudentActions from './StudentActions.vue';
import { VERIFICATION_BADGE, ACCOUNT_BADGE } from './studentBadges';
import type { Student } from '@/types';

defineProps<{ student: Student }>();

defineEmits<{
    verify: [];
    reject: [];
    suspend: [];
    reactivate: [];
}>();
</script>

<template>
    <tr class="align-middle transition-colors hover:bg-bg-surface border-b border-border-light last:border-b-0">
        <!-- Student -->
        <td class="px-5 py-3">
            <div class="flex items-center gap-3 min-w-0">
                <Avatar :name="student.name" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-text-primary truncate">{{ student.name }}</p>
                    <p class="text-xs text-text-muted">{{ student.section }}</p>
                </div>
            </div>
        </td>

        <!-- Student ID -->
        <td class="px-4 py-3">
            <span class="text-sm font-mono text-text-secondary">{{ student.student_id }}</span>
        </td>

        <!-- Year Level -->
        <td class="px-4 py-3">
            <span class="text-sm text-text-secondary">{{ student.year_level }}</span>
        </td>

        <!-- Verification -->
        <td class="px-4 py-3">
            <Badge
                :label="VERIFICATION_BADGE[student.verification_status].label"
                :variant="VERIFICATION_BADGE[student.verification_status].variant"
                :icon="VERIFICATION_BADGE[student.verification_status].icon"
            />
        </td>

        <!-- Account Status -->
        <td class="px-4 py-3">
            <Badge
                :label="ACCOUNT_BADGE[student.account_status].label"
                :variant="ACCOUNT_BADGE[student.account_status].variant"
                :icon="ACCOUNT_BADGE[student.account_status].icon"
            />
        </td>

        <!-- Actions -->
        <td class="px-4 py-3">
            <StudentActions
                :student="student"
                @verify="$emit('verify')"
                @reject="$emit('reject')"
                @suspend="$emit('suspend')"
                @reactivate="$emit('reactivate')"
            />
        </td>
    </tr>
</template>
