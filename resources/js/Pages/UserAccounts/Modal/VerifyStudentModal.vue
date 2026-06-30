<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import type { Student, PageProps } from '@/types';
import { useDismissibleError } from '@/composables/useDismissibleError';
import { useScrollGate } from '@/composables/useScrollGate';
import {
  XMarkIcon, CheckCircleIcon, UserCircleIcon, UserPlusIcon, XCircleIcon,
  EnvelopeIcon,
  KeyIcon,
  DevicePhoneMobileIcon,
  ExclamationTriangleIcon,
  ChevronDownIcon,
  AtSymbolIcon,
  ClipboardDocumentIcon,
  ArrowPathIcon,
  InboxArrowDownIcon,
  NoSymbolIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  verified: [];
}>();

const page = usePage<PageProps>();
const form = useForm({});
const rejectForm = useForm({});
const busy = computed(() => form.processing || rejectForm.processing);
const created = ref<{ email: string; password: string } | null>(null);
const copied = ref<'email' | 'password' | null>(null);
const revealPassword = ref(false);

const { errorMessage, errorPopup, showError, dismissError } = useDismissibleError();

// Make the admin scroll through the reminders before the action buttons appear.
const { contentEl, atBottom, updateAtBottom, scrollToBottom, reset: resetScrollGate } =
  useScrollGate();

// Reset the result view each time the modal is (re)opened.
watch(() => props.show, (open) => {
  if (open) {
    created.value = null;
    copied.value = null;
    dismissError();
    revealPassword.value = false;
    resetScrollGate();
  }
});

function submit() {
  if (!props.student) return;

  dismissError();

  form.post(route('student-accounts.registration.store', props.student.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const credentials = page.props.flash?.student_credentials;
      if (credentials) {
        created.value = credentials;   // switch to the result view
        if (page.props.flash) {
          page.props.flash.student_credentials = null;
        }
        emit('verified');              // refresh the list behind the modal
      } else {
        emit('verified');
        emit('close');
      }
    },
    onError: (errors) => {
      showError(errors.register ?? 'Account creation failed. Please try again.');
    },
  });
}

function reject() {
  if (!props.student) return;

  dismissError();

  rejectForm.delete(route('student-accounts.registration.destroy', props.student.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('verified');
      emit('close');
    },
    onError: (errors) => {
      showError(errors.reject ?? 'Failed to reject the student. Please try again.');
    },
  });
}

async function copy(field: 'email' | 'password') {
  if (!created.value) return;
  await navigator.clipboard.writeText(created.value[field]);
  copied.value = field;
  setTimeout(() => (copied.value = null), 1500);
}
</script>

<template>
  <Transition name="modal">
    <div v-if="show && student" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6" role="dialog"
      aria-modal="true">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

      <!-- Panel -->
      <div class="relative flex h-[90vh] w-full shrink-0 flex-col overflow-hidden rounded-md bg-white shadow-2xl
               sm:h-144 sm:w-160
               md:h-160 md:w-3xl
               lg:h-176 lg:w-4xl">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-6">
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-md"
              :class="created ? 'bg-emerald-50 text-emerald-600' : 'bg-sidebar/10 text-sidebar'">
              <CheckCircleIcon v-if="created" class="h-5 w-5" />
              <UserCircleIcon v-else class="h-5 w-5" />
            </span>
            <div>
              <h2 class="text-base font-bold leading-tight text-slate-800">
                {{ created ? 'Account Created' : 'Preview Account Details' }}
              </h2>
              <p class="text-xs text-slate-400">
                {{ created ? 'Credentials emailed to the student' : '' }}
              </p>
            </div>
          </div>

          <button type="button"
            class="inline-flex items-center justify-center rounded-sm border border-slate-200 bg-slate-200 p-2 text-slate-800 transition hover:border-red-500 hover:bg-red-100 hover:text-red-700"
            @click="emit('close')">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <!-- Privacy notice -->
        <div v-if="created"
          class="border-b border-amber-200 border-l-10 border-l-amber-400 bg-amber-50 px-4 py-3 text-center text-sm text-amber-800 sm:px-8">
          Initial password is hidden for privacy. Student authorization is required to view it.
        </div>

        <!-- Content -->
        <div ref="contentEl" class="flex-1 overflow-y-auto px-4 py-5 sm:px-8 sm:py-6" @scroll="updateAtBottom">

          <!-- Personal Information -->
          <section>
            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                Personal Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-2">

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  First Name
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.first_name }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Last Name
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.last_name }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Personal Email
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.personal_email || 'No email provided' }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Contact Number
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium text-slate-800">
                  {{ student.contact_number || 'No contact number provided' }}
                </p>
              </div>

            </div>
          </section>


          <!-- Academic Information -->
          <section class="mt-10">

            <div class="mb-6 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                Academic Information
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-3">

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Student ID
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 font-mono text-sm font-medium">
                  {{ student.student_id }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Program
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium">
                  {{ student.program || '—' }}
                </p>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                  Year Level
                </p>
                <p class="mt-2 border-b-2 border-slate-800 pb-2 text-sm font-medium">
                  {{ student.year_level || '—' }}
                </p>
              </div>

            </div>

          </section>


          <!-- Reminders -->
          <section v-if="!created" class="mt-10">

            <div class="mb-5 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                Before Creating This Account
              </h3>
              <span class="h-px flex-1 bg-slate-200" />
            </div>

            <ul class="space-y-6 text-sm leading-7 text-slate-700">
              <li class="flex gap-4">
                <ExclamationTriangleIcon class="mt-0.5 h-9 w-9 shrink-0 text-red-600" />
                <span class="text-red-700">
                  <strong class="font-semibold">
                    Rejecting this registration is permanent.
                  </strong>
                  The pending registration request will be deleted and cannot be recovered.
                </span>
              </li>

              <li class="flex gap-4">
                <CheckCircleIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  Verify that the student's
                  <strong class="font-semibold text-slate-900">
                    full name, ID number, program, and year level
                  </strong>
                  exactly match the official enrollment records before proceeding.
                </span>
              </li>

              <li class="flex gap-4">
                <AtSymbolIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  The student's
                  <strong class="font-semibold text-slate-900">login email is generated automatically</strong>
                  from their student number (e.g. <span class="font-mono">studentnumber@moodlink.com</span>). The
                  personal email is only used to deliver the credentials.
                </span>
              </li>

              <li class="flex gap-4">
                <EnvelopeIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  A
                  <strong class="font-semibold text-slate-900">
                    valid personal email address
                  </strong>
                  is required because login credentials are delivered there. The account cannot be created if this
                  information is missing or invalid.
                </span>
              </li>

              <li class="flex gap-4">
                <KeyIcon class="mt-0.5 h-9 w-9 shrink-0 text-amber-500" />
                <span>
                  The student will
                  <strong class="font-semibold text-slate-900">receive their initial password through their personal
                    email</strong>. Advise them to change it immediately after their first login to keep the account
                  secure.
                </span>
              </li>

              <li class="flex gap-4">
                <ClipboardDocumentIcon class="mt-0.5 h-9 w-9 shrink-0 text-amber-500" />
                <span>
                  The initial password is
                  <strong class="font-semibold text-slate-900">shown only once</strong>
                  on the next screen. Copy it before closing the dialog if needed — for privacy it cannot be retrieved
                  again later.
                </span>
              </li>

              <li class="flex gap-4">
                <InboxArrowDownIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  Remind the student to check their
                  <strong class="font-semibold text-slate-900">inbox and spam / junk folder</strong>
                  for the credentials email if it does not appear right away.
                </span>
              </li>

              <li class="flex gap-4">
                <ArrowPathIcon class="mt-0.5 h-9 w-9 shrink-0 text-amber-500" />
                <span>
                  There is
                  <strong class="font-semibold text-slate-900">no self-service password reset</strong>
                  in the mobile app. If a student forgets or loses their password, they must request a reset through the
                  GCU administrator.
                </span>
              </li>

              <li class="flex gap-4">
                <NoSymbolIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  To revoke access without deleting data, use
                  <strong class="font-semibold text-slate-900">Suspend</strong> — suspended accounts keep all records
                  and
                  can be reactivated anytime. Rejection is only for pending registrations and is permanent.
                </span>
              </li>

              <li class="flex gap-4">
                <DevicePhoneMobileIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  Creating this account grants the student
                  <strong class="font-semibold text-slate-900">
                    access to the MoodLink mobile application
                  </strong>
                  and its available features.
                </span>
              </li>

              <li class="flex gap-4">
                <ShieldCheckIcon class="mt-0.5 h-9 w-9 shrink-0 text-blue-500" />
                <span>
                  Student mood entries and posts are
                  <strong class="font-semibold text-slate-900">confidential</strong>. Only perform account actions that
                  are necessary and authorized, and never share a student's credentials with anyone else.
                </span>
              </li>



            </ul>

          </section>


          <!-- Generated credentials -->
          <section v-if="created" class="mt-10">

            <div class="mb-4 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                Initial Login Credentials
              </h3>
              <span class="h-px flex-1 bg-slate-300" />
            </div>




            <div class="grid grid-cols-1 gap-x-10 gap-y-6 sm:grid-cols-2">

              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Email
                </p>

                <div class="mt-2 flex items-center border-b-2 border-emerald-700 pb-2">
                  <span class="flex-1 truncate font-mono text-sm font-medium text-slate-800">
                    {{ created.email }}
                  </span>

                  <button type="button" class="text-xs font-semibold text-sidebar hover:underline"
                    @click="copy('email')">
                    {{ copied === 'email' ? 'Copied' : 'Copy' }}
                  </button>
                </div>

              </div>


              <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  Initial Password
                </p>

                <div class="mt-2 flex items-center gap-3 border-b-2 border-emerald-700 pb-2">
                  <span class="flex-1 truncate font-mono text-sm font-medium text-slate-800">
                    {{ revealPassword ? created.password : '••••••••' }}
                  </span>

                  <button type="button" class="text-xs font-semibold text-slate-500 hover:underline"
                    @click="revealPassword = !revealPassword">
                    {{ revealPassword ? 'Hide' : 'Show' }}
                  </button>

                  <button type="button" class="text-xs font-semibold text-sidebar hover:underline"
                    @click="copy('password')">
                    {{ copied === 'password' ? 'Copied' : 'Copy' }}
                  </button>
                </div>
              </div>

            </div>

          </section>
        </div>

        <!-- Footer: only the action buttons -->
        <div v-if="!created" class="relative mt-auto bg-slate-100 px-4 py-5 sm:px-8 sm:py-6">

          <!-- Floating error -->
          <Transition name="modal">
            <div v-if="errorMessage" ref="errorPopup"
              class="absolute inset-x-4 bottom-full z-10 mb-2 rounded-md border-l-10 border-r-10 border-red-500 bg-red-50 px-4 py-3 text-center text-sm text-red-700 shadow-lg sm:inset-x-8">
              {{ errorMessage }}
            </div>
          </Transition>

          <!-- Scroll gate -->
          <button v-if="!atBottom" type="button" @click="scrollToBottom"
            class="flex w-full items-center justify-center gap-2 rounded-sm border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
            <ChevronDownIcon class="h-5 w-5 animate-bounce text-blue-600" />
            Scroll down and review the reminders to continue
          </button>

          <!-- Buttons appear only after the reminders have been scrolled through -->
          <div v-else class="flex flex-col gap-3 sm:flex-row">

            <button type="button"
              class="flex items-center justify-center gap-2 bg-sidebar px-6 py-2 text-sm rounded-sm font-semibold text-white transition hover:bg-sidebar/90 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="submit">
              <UserPlusIcon class="h-5 w-5" />
              {{ form.processing ? 'Create Account' : 'Create Account' }}
            </button>


            <button type="button"
              class="flex items-center justify-center gap-2 bg-red-600 px-6 py-2 text-sm rounded-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="busy" @click="reject">
              <XCircleIcon class="h-5 w-5" />
              {{ rejectForm.processing ? 'Reject' : 'Reject' }}
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
