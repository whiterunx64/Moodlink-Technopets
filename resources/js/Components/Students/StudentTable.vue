<script setup lang="ts">
import { UserMinusIcon } from '@heroicons/vue/24/outline';
import StudentRow from './StudentRow.vue';
import type { Student } from '@/types';

defineProps<{
    rows: Student[];
}>();

const emit = defineEmits<{
    register: [student: Student];
    verify: [student: Student];
    reject: [student: Student];
    suspend: [student: Student];
    reactivate: [student: Student];
}>();
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm overflow-hidden flex flex-col">
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
                        @register="emit('register', student)" 
                        @verify="emit('verify', student)"
                        @reject="emit('reject', student)"
                        @suspend="emit('suspend', student)"
                        @reactivate="emit('reactivate', student)"
                    />

                    <!-- Empty state -->
                    <tr v-if="rows.length === 0">
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
    </div>
</template>
