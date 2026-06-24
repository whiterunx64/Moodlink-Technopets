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
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm sm:rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-160">
                <colgroup>
                    <col class="w-56 sm:w-72 md:w-96 lg:w-148" />
                    <col class="w-24 sm:w-28 lg:w-36" />
                    <col class="w-20 sm:w-24 lg:w-28" />
                    <col class="w-24 sm:w-28 lg:w-32" />
                    <col class="w-24 sm:w-28 lg:w-35" />
                    <col class="w-24 sm:w-32 lg:w-40" />
                </colgroup>
                <thead>
                    <tr class="border-b border-table-grid bg-table-header">
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-4 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-5 lg:py-3.5">
                            Student
                        </th>
                        <th class="px-2.5 py-2.5 text-left text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-3 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-4 lg:py-3.5">
                            Student ID
                        </th>
                        <th class="px-2.5 py-2.5 text-left text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-3 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-4 lg:py-3.5">
                            Year Level
                        </th>
                        <th class="px-2.5 py-2.5 text-left text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-3 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-4 lg:py-3.5">
                            Verification
                        </th>
                        <th class="px-2.5 py-2.5 text-left text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-3 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-4 lg:py-3.5">
                            Account Status
                        </th>
                        <th class="px-2.5 py-2.5 text-right text-[10px] font-semibold tracking-wider text-sidebar/70 uppercase sm:px-3 sm:py-3 sm:text-[11px] sm:tracking-widest lg:px-4 lg:py-3.5">
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
                            <div class="flex h-48 flex-col items-center justify-center gap-2 sm:h-56 sm:gap-3 lg:h-64">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sidebar/10 sm:h-14 sm:w-14">
                                    <UserMinusIcon class="h-6 w-6 text-sidebar/50 sm:h-7 sm:w-7" />
                                </div>
                                <p class="text-xs font-medium text-gray-400 sm:text-sm">No students found</p>
                                <p class="text-[11px] text-gray-300 sm:text-xs">Try adjusting your search or filter options</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
