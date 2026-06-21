<script setup lang="ts">
import { computed } from 'vue';
import type { Student } from '@/types';
import { XMarkIcon, UserCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  suspend: [];
  reactivate: [];
}>();

const isSuspended = computed(() => props.student?.account_status === 'suspended');
</script>

<template>
  <Transition name="modal">
    <div v-if="show && student" class="fixed inset-0 z-50 flex items-center justify-center p-6" role="dialog"
      aria-modal="true">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

      <!-- Panel -->
      <div class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-md bg-white shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-sidebar/10 text-sidebar">
              <UserCircleIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-bold leading-tight text-slate-800">
                Account Details
              </h2>
              <p class="text-xs text-slate-400">
                {{ isSuspended ? 'This account is suspended' : 'This account is active' }}
              </p>
            </div>
          </div>

          <button type="button"
            class="inline-flex items-center justify-center rounded-sm border border-slate-200 bg-slate-200 p-2 text-slate-800 transition hover:border-red-500 hover:bg-red-100 hover:text-red-700"
            @click="emit('close')">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-8 py-6">

          <!-- Personal Information -->
          <section>
            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Personal Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-2 gap-x-10 gap-y-6">

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  First Name
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.first_name }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Last Name
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.last_name }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Personal Email
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.personal_email || '—' }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Contact Number
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.contact_number || '—' }}
                </p>
              </div>

            </div>
          </section>


          <!-- Academic Information -->
          <section class="mt-10">

            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Academic Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-3 gap-x-10 gap-y-6">

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Student ID
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 font-mono text-sm font-medium">
                  {{ student.student_id }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Section
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium">
                  {{ student.section || '—' }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Year Level
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium">
                  {{ student.year_level || '—' }}
                </p>
              </div>

            </div>

          </section>

        </div>

        <!-- Footer -->
        <div class="mt-auto bg-slate-100 px-8 py-6">

          <div class="flex items-center justify-between">

            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
              Account Status
            </span>

            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="isSuspended
              ? 'bg-red-100 text-red-700'
              : 'bg-emerald-100 text-emerald-700'">
              {{ isSuspended ? 'Account Suspended' : 'Account Verified' }}
            </span>

          </div>


          <div class="mt-4 border-l-10 bg-white px-4 py-3 text-sm text-slate-600" :class="isSuspended
            ? 'border-red-500'
            : 'border-emerald-500'">

            <template v-if="isSuspended">
              This account is currently suspended. The student is unable to authenticate and access any features within
              the MoodLink mobile application. Reactivating the account will immediately restore access while preserving
              all existing records and account data.
            </template>

            <template v-else>
              This account is currently active and in good standing. The student can authenticate and use all features
              available within the MoodLink mobile application. Suspending the account will immediately revoke access
              without affecting existing records or account data.
            </template>

          </div>

          <!-- Suspended: reactivate -->
          <div v-if="isSuspended" class="mt-4 flex gap-3">
            <button type="button" class="bg-sidebar px-6 py-2 text-sm font-semibold text-white hover:bg-sidebar/90"
              @click="emit('reactivate')">
              Reactivate Account
            </button>
          </div>

          <!-- Active: suspend -->
          <div v-else class="mt-4 flex gap-3">
            <button type="button" class="bg-orange-500 px-6 py-2 text-sm font-semibold text-white hover:bg-orange-600"
              @click="emit('suspend')">
              Suspend Account
            </button>
          </div>

        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition:
    opacity 200ms ease,
    transform 200ms ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from>div:last-child,
.modal-leave-to>div:last-child {
  transform: scale(0.96) translateY(10px);
}
</style>
