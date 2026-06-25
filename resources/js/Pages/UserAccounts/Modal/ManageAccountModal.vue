<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import type { Student, PageProps } from '@/types';
import { useDismissibleError } from '@/composables/useDismissibleError';
import { XMarkIcon, UserCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DeleteAccountModal from '@/Pages/UserAccounts/Modal/DeleteAccountModal.vue';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  updated: [];
}>();

const page = usePage<PageProps>();

// One form per action so each tracks its own in-flight state.
const suspendForm = useForm({});
const reactivateForm = useForm({});
const busy = computed(() => suspendForm.processing || reactivateForm.processing);
const accountStatus = ref<Student['account_status']>(props.student?.account_status ?? 'active');
const isSuspended = computed(() => accountStatus.value === 'suspended');

const deleteModalOpen = ref(false);

const { errorMessage, errorPopup, showError, dismissError } = useDismissibleError();
void errorPopup; // bound via ref="errorPopup" in the template

// Reset the local status whenever a (new) student is shown.
watch(() => props.show, (open) => {
  if (open) {
    accountStatus.value = props.student?.account_status ?? 'active';
    deleteModalOpen.value = false;
    dismissError();
  }
});

function runAction(
  formObj: typeof suspendForm,
  routeName: string,
  nextStatus: Student['account_status'],
  failureMessage: string,
) {
  if (!props.student) return;
  if (!props.student.auth_user_id) {
    showError('This student does not have an authentication account yet, so it cannot be managed.');
    return;
  }

  dismissError();

  formObj.patch(route(routeName, props.student.auth_user_id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const flashError = page.props.flash?.error;
      if (flashError) {
        showError(flashError);
        return;
      }

      accountStatus.value = nextStatus;
      emit('updated');
    },
    onError: () => showError(failureMessage),
  });
}

function suspend() {
  runAction(suspendForm, 'student-accounts.restrict-access', 'suspended', 'Failed to suspend the account. Please try again.');
}

function reactivate() {
  runAction(reactivateForm, 'student-accounts.restore-access', 'active', 'Failed to reactivate the account. Please try again.');
}

function onDeleted() {
  deleteModalOpen.value = false;
  emit('close');
  emit('updated');
}
</script>

<template>
  <Transition name="modal">
    <div v-if="show && student" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6" role="dialog"
      aria-modal="true">
      <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

      <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-md bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-6">
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

        <div class="flex-1 overflow-y-auto px-4 py-5 sm:px-8 sm:py-6">

          <section>
            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Personal Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-2">

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


          <section class="mt-10">

            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Academic Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-3">

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
                  Program
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium">
                  {{ student.program || '—' }}
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

        <div class="relative mt-auto bg-slate-100 px-4 py-5 sm:px-8 sm:py-6">

          <!-- Error overlays the footer so it never grows the modal height -->
          <Transition name="modal">
            <div v-if="errorMessage" ref="errorPopup"
              class="absolute inset-x-4 bottom-full z-10 mb-2 rounded-md border-l-10 border-red-500 bg-red-50 px-4 py-3 text-center text-sm text-red-700 shadow-lg sm:inset-x-8">
              {{ errorMessage }}
            </div>
          </Transition>

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

          <div v-if="isSuspended" class="mt-4 flex flex-col gap-3 sm:flex-row">
            <button type="button"
              class="flex items-center justify-center bg-sidebar px-6 py-2 text-sm font-semibold text-white transition hover:bg-sidebar/90 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="reactivate">
              {{ reactivateForm.processing ? 'Reactivating…' : 'Reactivate Account' }}
            </button>
            <button type="button"
              class="flex items-center justify-center gap-2 border border-red-300 bg-white px-6 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="deleteModalOpen = true">
              <TrashIcon class="h-4 w-4" />
              Delete Account
            </button>
          </div>

          <div v-else class="mt-4 flex flex-col gap-3 sm:flex-row">
            <button type="button"
              class="flex items-center justify-center bg-orange-500 px-6 py-2 text-sm font-semibold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="suspend">
              {{ suspendForm.processing ? 'Suspending…' : 'Suspend Account' }}
            </button>
            <button type="button"
              class="flex items-center justify-center gap-2 border border-red-300 bg-white px-6 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="deleteModalOpen = true">
              <TrashIcon class="h-4 w-4" />
              Delete Account
            </button>
          </div>

        </div>

      </div>
    </div>
  </Transition>

  <!-- Delete confirmation sub-modal -->
  <DeleteAccountModal :show="deleteModalOpen" :student="student" @close="deleteModalOpen = false"
    @deleted="onDeleted" />
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
