<script setup lang="ts">
import { UserMinusIcon } from '@heroicons/vue/24/outline';
import StudentRow from './StudentRow.vue';
import type { Student } from '@/types';

defineProps<{
    rows: Student[];
}>();

const emit = defineEmits<{
    open: [student: Student];
}>();
</script>

<template>
    <div class="bg-table-body rounded-t-lg border border-border-light shadow-lg overflow-hidden flex flex-col">
        <div class="h-150 overflow-x-auto overflow-y-auto">
            <table class="w-full">
                <colgroup>
                    <col class="w-148" />
                    <col class="w-36" />
                    <col class="w-28" />
                    <col class="w-32" />
                    <col class="w-35" />
                    <col class="w-48" />
                </colgroup>
                <thead>
                    <tr class="border-b border-border-light bg-table-header">
                        <th
                            class="px-5 py-3 text-left text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Student</th>
                        <th
                            class="px-4 py-3 text-left text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Student ID</th>
                        <th
                            class="px-4 py-3 text-left text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Year Level</th>
                        <th
                            class="px-4 py-3 text-left text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Verification</th>
                        <th
                            class="px-4 py-3 text-left text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Account Status</th>
                        <th
                            class="px-4 py-3 text-right text-[12px] font-semibold tracking-widest text-text-muted uppercase">
                            Manageable student</th>
                    </tr>
                </thead>
                <tbody>
                    <StudentRow v-for="student in rows" :key="student.id" :student="student"
                        @open="emit('open', student)" />

                    <!-- Empty state -->
                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-0">
                            <div class="flex h-135 flex-col items-center justify-center gap-2 text-gray-600">
                                <UserMinusIcon class="w-14 h-14" />
                                <p class="text-sm">No students found matching your current filters. Try adjusting your
                                    search or filter options.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
