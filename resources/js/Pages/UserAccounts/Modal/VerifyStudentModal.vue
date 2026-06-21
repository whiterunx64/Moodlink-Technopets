<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import type { Student, PageProps } from '@/types';
import { XMarkIcon, CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  verified: [];
}>();

const page = usePage<PageProps>();

// No fields — email & initial password are generated on the server.
const form = useForm({});

// Holds the generated credentials after a successful creation (shown once).
const created = ref<{ email: string; password: string } | null>(null);
const copied = ref<'email' | 'password' | null>(null);

// Password is masked by default; the admin must explicitly reveal it.
const revealPassword = ref(false);

// Inline error shown when registration fails (e.g. invalid email).
const errorMessage = ref<string | null>(null);

// Scroll hint: shown only while credentials exist and the user hasn't
// scrolled to the bottom of the content yet.
const contentEl = ref<HTMLElement | null>(null);
const atBottom = ref(false);

function onScroll() {
  const el = contentEl.value;
  if (!el) return;
  atBottom.value = el.scrollTop + el.clientHeight >= el.scrollHeight - 8;
}

// Reset the result view each time the modal is (re)opened.
watch(() => props.show, (open) => {
  if (open) {
    created.value = null;
    copied.value = null;
    errorMessage.value = null;
    atBottom.value = false;
    revealPassword.value = false;
  }
});

function submit() {
  if (!props.student) return;

  errorMessage.value = null;

  form.post(route('user-accounts.register', props.student.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const credentials = page.props.flash?.student_crendetials;
      if (credentials) {
        created.value = credentials;   // switch to the result view
        emit('verified');              // refresh the list behind the modal
      } else {
        emit('verified');
        emit('close');
      }
    },
    onError: (errors) => {
      errorMessage.value = errors.register ?? 'Account creation failed. Please try again.';
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
    <div v-if="show && student" class="fixed inset-0 z-50 flex items-center justify-center p-6" role="dialog"
      aria-modal="true">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

      <!-- Panel -->
      <div class="relative flex h-170 w-full max-w-3xl flex-col overflow-hidden rounded-md bg-white shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
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

        <!-- Privacy notice (shown once the account is created) -->
        <div v-if="created"
          class="border-b border-amber-200 border-l-10 border-l-amber-400 bg-amber-50 px-8 py-3 text-center text-sm text-amber-800">
          Initial password is hidden for privacy. Student authorization is required to view it.
        </div>

        <!-- Content -->
        <div ref="contentEl" class="flex-1 overflow-y-auto px-8 py-6" @scroll="onScroll">

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


          <!-- Generated credentials -->
          <section v-if="created" class="mt-10">

            <div class="mb-4 flex items-center gap-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                Initial Login Credentials
              </h3>
              <span class="h-px flex-1 bg-slate-300" />
            </div>




            <div class="grid grid-cols-2 gap-x-10 gap-y-6">

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

          <!-- Scroll hint: visible pill, only when credentials exist and not yet at bottom -->
          <div v-if="created && !atBottom" class="pointer-events-none sticky bottom-3 z-10 flex justify-center">
            <div
              class="flex max-w-md items-center gap-3 rounded-lg bg-slate-900/90 px-4 py-2.5 text-white shadow-lg ring-1 ring-black/5">
              <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
              </svg>
              <span class="text-xs font-medium leading-snug">
                Scroll down to view the credential access section
              </span>
            </div>
          </div>
        </div>

        <!-- Inline error (between the form and the footer / status container) -->
        <div v-if="errorMessage"
          class="border-l-10 border-red-500 bg-red-50 px-8 py-3 text-center text-sm text-red-700">
          {{ errorMessage }}
        </div>

        <!-- Footer -->
        <div class="mt-auto bg-slate-100 px-8 py-6">

          <div class="flex items-center justify-between">

            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
              Account Status
            </span>

            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="created
              ? 'bg-emerald-100 text-emerald-700'
              : 'bg-amber-100 text-amber-700'">
              {{ created ? 'Account Verified' : 'Pending Verification' }}
            </span>

          </div>


          <div class="mt-4 border-l-10 bg-white px-4 py-3 text-sm text-slate-600" :class="created
            ? 'border-emerald-500'
            : 'border-amber-400'">

            <template v-if="created">
              The user has been verified. Instruct them to check their personal Email to find their password so they can
              log in.
            </template>

            <template v-else>
              Before proceeding with verification, please ensure the student's information is correct and the personal
              email address is valid.
            </template>

          </div>

          <div v-if="!created" class="mt-4 flex gap-3">

            <button type="button" class="bg-sidebar px-6 py-2 text-sm font-semibold text-white hover:bg-sidebar/90"
              :disabled="form.processing" @click="submit">
              {{ form.processing ? 'Creating…' : 'Create Account' }}
            </button>


            <button type="button" class="bg-red-600 px-6 py-2 text-sm font-semibold text-white hover:bg-red-700"
              @click="emit('close')">
              Reject
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
