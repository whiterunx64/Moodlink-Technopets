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
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <colgroup>
                    <col class="w-148" />
                    <col class="w-36" />
                    <col class="w-28" />
                    <col class="w-32" />
                    <col class="w-35" />
                    <col class="w-40" />
                </colgroup>
                <thead>
                    <tr class="border-b border-table-grid bg-table-header">
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Student
                        </th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Student ID
                        </th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Year Level
                        </th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Verification
                        </th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Account Status
                        </th>
                        <th class="px-4 py-3.5 text-right text-[11px] font-semibold tracking-widest text-sidebar/70 uppercase">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-table-grid">
                    <StudentRow
                        v-for="student in rows"
                        :key="student.id"
                        :student="student"
                        @open="emit('open', student)"
                    />

                    <!-- Empty state -->
                    <tr v-if="rows.length === 0">
                        <td colspan="6" class="p-0">
                            <div class="flex h-64 flex-col items-center justify-center gap-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sidebar/10">
                                    <UserMinusIcon class="h-7 w-7 text-sidebar/50" />
                                </div>
                                <p class="text-sm font-medium text-gray-400">No students found</p>
                                <p class="text-xs text-gray-300">Try adjusting your search or filter options</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
