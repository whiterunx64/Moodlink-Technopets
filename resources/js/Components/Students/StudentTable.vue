<script setup lang="ts">
import { UserMinusIcon } from '@heroicons/vue/24/outline';
import StudentRow from './StudentRow.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import type { Student } from '@/types';

defineProps<{
    rows: Student[];
    total: number;
    currentPage: number;
    totalPages: number;
    pageNumbers: (number | '…')[];
    rangeStart: number;
    rangeEnd: number;
}>();

const emit = defineEmits<{
    verify: [student: Student];
    reject: [student: Student];
    suspend: [student: Student];
    reactivate: [student: Student];
    'update:currentPage': [page: number];
    prev: [];
    next: [];
}>();
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm overflow-hidden flex flex-col min-h-130">
        <div class="flex-1 min-h-0 overflow-x-auto">
            <table class="w-full">
                <colgroup>
                    <col />
                    <col class="w-36" />
                    <col class="w-28" />
                    <col class="w-32" />
                    <col class="w-32" />
                    <col class="w-48" />
                </colgroup>
                <thead>
                    <tr class="border-b border-border-light bg-bg-surface">
                        <th class="px-5 py-3 text-left text-[10px] font-semibold tracking-widest text-text-muted uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold tracking-widest text-text-muted uppercase">Student ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold tracking-widest text-text-muted uppercase">Year Level</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold tracking-widest text-text-muted uppercase">Verification</th>
                        <th class="px-4 py-3 text-left text-[10px] font-semibold tracking-widest text-text-muted uppercase">Account Status</th>
                        <th class="px-4 py-3 text-right text-[10px] font-semibold tracking-widest text-text-muted uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <StudentRow
                        v-for="student in rows"
                        :key="student.id"
                        :student="student"
                        @verify="emit('verify', student)"
                        @reject="emit('reject', student)"
                        @suspend="emit('suspend', student)"
                        @reactivate="emit('reactivate', student)"
                    />

                    <!-- Empty state -->
                    <tr v-if="total === 0">
                        <td colspan="6" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-gray-300">
                                <UserMinusIcon class="w-8 h-8" />
                                <p class="text-sm">No students found</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            v-if="total > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :page-numbers="pageNumbers"
            :range-start="rangeStart"
            :range-end="rangeEnd"
            :total="total"
            @update:current-page="emit('update:currentPage', $event)"
            @prev="emit('prev')"
            @next="emit('next')"
        />
    </div>
</template>
