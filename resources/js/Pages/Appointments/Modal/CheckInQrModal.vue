<script setup lang="ts">
import type { Appointment, CheckInReadyAppointment } from '@/types';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
  appointment: Appointment | CheckInReadyAppointment;
  qrDataUrl: string;
  checkedIn?: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

const nowMs = ref(Date.now());
const ticker = window.setInterval(() => {
  nowMs.value = Date.now();
}, 1000);
onBeforeUnmount(() => window.clearInterval(ticker));

const expiresAtMs = computed(() =>
  props.appointment.checkin_expires_at ? new Date(props.appointment.checkin_expires_at).getTime() : null,
);

const remainingMs = computed(() =>
  expiresAtMs.value === null ? null : Math.max(0, expiresAtMs.value - nowMs.value),
);

const isExpired = computed(() => remainingMs.value !== null && remainingMs.value <= 0);

const isLastMinute = computed(() => remainingMs.value !== null && remainingMs.value <= 60_000);

const countdownLabel = computed(() => {
  if (remainingMs.value === null) {
    return null;
  }
  const totalSeconds = Math.floor(remainingMs.value / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});
</script>

<template>
  <Teleport to="body">
    <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-opacity duration-100" leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="appointment" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60" @click="emit('close')" />

        <!-- Modal -->
        <div class="relative w-full max-w-4xl overflow-hidden rounded-md border border-gray-300 bg-white shadow-xl">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-300 px-6 py-4">
            <div>
              <h3 class="text-xl font-semibold text-gray-900">
                Session Check-in
              </h3>

              <p class="mt-1 text-sm text-gray-600">
                Verify session details before recording attendance.
              </p>
            </div>

            <button type="button" class="rounded p-2 text-gray-700 hover:bg-gray-100" @click="emit('close')">
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <!-- Content -->
          <div class="grid md:grid-cols-[1fr_340px]">
            <!-- Left -->
            <div class="p-6">
              <div class="space-y-5">
                <div class="space-y-5">
                  <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-700">
                      Student
                    </p>

                    <div class="mt-1 border-b-2 border-gray-400 pb-2">
                      <p class="text-lg font-bold text-gray-900">
                        {{ appointment.student_name }}
                      </p>
                    </div>
                  </div>

                  <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-700">
                      Scheduled Time
                    </p>

                    <div class="mt-1 border-b-2 border-gray-400 pb-2">
                      <p class="text-base font-bold text-gray-900">
                        {{ appointment.time }}
                      </p>
                    </div>
                  </div>

                  <div v-if="'context' in appointment && appointment.context">
                    <p class="text-sm font-bold uppercase tracking-wide text-gray-700">
                      Session Type
                    </p>

                    <div class="mt-1 border-b-2 border-gray-400 pb-2">
                      <p class="text-base font-bold text-gray-900">
                        {{ appointment.context }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="border border-amber-300 bg-amber-50 p-4">
                  <p class="font-semibold text-amber-900">
                    Important Notice
                  </p>

                  <p class="mt-2 text-sm leading-relaxed text-amber-800">
                    This QR code is available only during the first
                    <strong>30 minutes</strong>
                    of the scheduled session.
                  </p>

                  <p class="mt-2 text-sm leading-relaxed text-amber-800">
                    If attendance is not recorded before the QR code expires,
                    the appointment will automatically be marked as
                    <strong>Missed</strong>.
                  </p>
                </div>

                <div class="border border-gray-200 bg-gray-50 p-4">
                  <p class="font-semibold text-gray-900">
                    Instructions
                  </p>

                  <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
                    <li>Verify the student's information.</li>
                    <li>Start the session as scheduled.</li>
                    <li>Scan the QR code before it expires.</li>
                    <li>Attendance will be recorded automatically.</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- QR Panel -->
            <div class="border-t border-gray-300 bg-gray-50 p-6 md:border-t-0 md:border-l">
              <div class="flex h-full flex-col items-center justify-center">
                <template v-if="checkedIn">
                  <div
                    class="flex aspect-square w-full max-w-70 items-center justify-center border-2 border-green-300 bg-green-50 p-4 text-center">
                    <div>
                      <CheckCircleIcon class="mx-auto h-14 w-14 text-green-600" />
                      <p class="mt-2 text-base font-bold text-green-700">Checked In</p>
                      <p class="mt-1 text-sm text-gray-600">Attendance recorded successfully.</p>
                    </div>
                  </div>

                  <p class="mt-4 text-center text-sm text-green-700">
                    The session has been marked as completed.
                  </p>
                </template>

                <template v-else-if="isExpired">
                  <div
                    class="flex aspect-square w-full max-w-70 items-center justify-center border-2 border-dashed border-red-300 bg-white p-4 text-center">
                    <div>
                      <p class="text-base font-bold text-red-600">QR Code Expired</p>
                      <p class="mt-1 text-sm text-gray-600">The check-in window has closed.</p>
                    </div>
                  </div>

                  <p class="mt-4 text-center text-sm text-red-600">
                    This appointment will be marked as Missed.
                  </p>
                </template>

                <template v-else>
                  <div class="border-2 border-gray-300 bg-white p-4">
                    <img v-if="qrDataUrl" :src="qrDataUrl" alt="Session check-in QR code"
                      class="block w-full max-w-70" />
                  </div>

                  <p v-if="countdownLabel" class="mt-4 text-center">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">
                      Expires in
                    </span>
                    <span class="font-mono text-3xl font-bold tabular-nums"
                      :class="isLastMinute ? 'text-red-600' : 'text-gray-900'">
                      {{ countdownLabel }}
                    </span>
                  </p>

                  <p class="mt-2 text-center text-sm text-gray-700">
                    Scan this QR code to record attendance
                  </p>
                </template>

                <button type="button"
                  class="mt-6 w-full rounded border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-white"
                  @click="emit('close')">
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>