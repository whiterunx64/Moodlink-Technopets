<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import type { Student, PageProps } from '@/types';
import { TrashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  deleted: [];
}>();

const page = usePage<PageProps>();
const deleteForm = useForm({});

function confirm() {
  if (!props.student) return;
  deleteForm.delete(route('student-accounts.destroy', props.student.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      if (page.props.flash?.error) return;
      emit('deleted');
    },
  });
}
</script>

<template>
  <Transition name="submodal">
    <div v-if="show && student" class="fixed inset-0 z-60 flex items-center justify-center p-4 sm:p-6"
      role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
      <div class="absolute inset-0 bg-slate-900/60" @click="emit('close')" />

      <div class="relative w-full max-w-md overflow-hidden rounded-md bg-white shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 border-b border-red-100 bg-red-50 px-5 py-4">
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-red-100 text-red-600">
              <TrashIcon class="h-5 w-5" />
            </span>
            <div>
              <h2 id="delete-modal-title" class="text-base font-bold leading-tight text-slate-800">
                Delete Account
              </h2>
              <p class="text-xs text-red-500">This action is permanent and cannot be undone</p>
            </div>
          </div>

        </div>

        <!-- Body -->
        <div class="px-5 py-6">
          <div class="flex gap-4">
            <ExclamationTriangleIcon class="mt-0.5 h-6 w-6 shrink-0 text-red-500" />
            <div class="space-y-2 text-sm text-slate-700">
              <p>
                You are about to permanently delete the account of
                <strong class="font-semibold text-slate-900">{{ student.first_name }} {{ student.last_name }}</strong>
                ({{ student.student_id }}).
              </p>
              <p>
                This will remove their student record and revoke their MoodLink authentication account.
                All associated data will be lost.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="relative bg-slate-50 px-5 py-4">
          <div class="flex flex-col gap-3 sm:flex-row-reverse">
            <button type="button"
              class="flex items-center justify-center gap-2 bg-red-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="deleteForm.processing" @click="confirm">
              <TrashIcon class="h-4 w-4" />
              {{ deleteForm.processing ? 'Deleting…' : 'Yes, Delete Account' }}
            </button>

            <button type="button"
              class="flex items-center justify-center border border-slate-300 bg-white px-6 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="deleteForm.processing" @click="emit('close')">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.submodal-enter-active,
.submodal-leave-active {
  transition:
    opacity 180ms ease,
    transform 180ms ease;
}

.submodal-enter-from,
.submodal-leave-to {
  opacity: 0;
}

.submodal-enter-from > div:last-child,
.submodal-leave-to > div:last-child {
  transform: scale(0.95) translateY(8px);
}
</style>
