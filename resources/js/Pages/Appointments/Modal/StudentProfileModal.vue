<script setup lang="ts">
import type { AppointmentStudentProfile } from '@/types';
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps<{
  student: AppointmentStudentProfile & { name: string };
  statusBadge: Record<string, string>;
}>();

const emit = defineEmits<{
  close: [];
}>();

function formatDateTime(date: string, time: string) {
  return new Date(date + ' ' + time).toLocaleString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="emit('close')" />

      <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
        <button type="button"
          class="text-text-muted absolute top-4 right-4 z-10 rounded-full p-1.5 transition-colors hover:bg-gray-100"
          @click="emit('close')">
          <XMarkIcon class="h-4 w-4" />
        </button>

        <div class="p-7">
          <!-- Profile Header -->
          <div class="mb-6 flex items-center gap-4">
            <div class="bg-sidebar/10 flex h-16 w-16 shrink-0 items-center justify-center rounded-full">
              <span class="text-sidebar text-xl font-bold">
                {{ student.initials }}
              </span>
            </div>
            <div>
              <p class="text-text-primary text-lg font-bold">
                {{ student.name }}
              </p>
              <p class="text-text-muted mt-0.5 text-sm">
                {{ student.student_id }} &bull;
                {{ student.program }} &bull;
                {{ student.year_level }}
              </p>
              <p class="text-text-muted text-sm">
                {{ student.email }}
              </p>
            </div>
          </div>

          <!-- Info Grid -->
          <div class="mb-6 grid grid-cols-2 gap-4 rounded-xl bg-gray-50 p-4">
            <div>
              <p class="text-text-muted text-xs">Program</p>
              <p class="text-text-primary mt-0.5 text-sm font-semibold">
                {{ student.program }}
              </p>
            </div>
            <div>
              <p class="text-text-muted text-xs">Year Level</p>
              <p class="text-text-primary mt-0.5 text-sm font-semibold">
                {{ student.year_level }}
              </p>
            </div>
            <div>
              <p class="text-text-muted text-xs">Student ID</p>
              <p class="text-text-primary mt-0.5 text-sm font-semibold">
                {{ student.student_id }}
              </p>
            </div>
            <div>
              <p class="text-text-muted text-xs">Total Appointments</p>
              <p class="text-text-primary mt-0.5 text-sm font-semibold">
                {{ student.total_appointments }}
              </p>
            </div>
          </div>

          <!-- Appointment History -->
          <p class="text-text-primary mb-3 text-sm font-semibold">
            Appointment History
          </p>
          <div class="space-y-3">
            <div v-for="(h, i) in student.history" :key="i"
              class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 px-4 py-3">
              <div class="min-w-0">
                <p class="text-text-primary text-sm font-semibold">
                  {{ h.context }}
                </p>
                <p class="text-text-muted mt-0.5 text-xs">
                  {{ formatDateTime(h.date, h.time) }}
                </p>
                <p v-if="h.note" class="text-text-muted mt-1 text-xs italic">
                  "{{ h.note }}"
                </p>
              </div>
              <span :class="[
                'shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                statusBadge[h.status] ?? 'bg-gray-100 text-gray-600',
              ]">
                {{ h.status === 'Completed' ? 'Done' : h.status }}
              </span>
            </div>
            <p v-if="student.history.length === 0" class="text-text-muted py-4 text-center text-xs">
              No appointment history.
            </p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
