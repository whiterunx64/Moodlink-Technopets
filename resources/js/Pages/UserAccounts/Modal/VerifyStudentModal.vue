<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Student } from '@/types';

const props = defineProps<{
  student: Student | null;
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  verified: [];
}>();

const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  password_confirmation: '',
});

watch(() => props.show, (open) => {
  if (open) {
    form.reset();
    showPassword.value = false;
  }
});

function submit() {
    if (!props.student) return;

    console.log('submitting for student:', props.student.id);
    console.log('form data:', form.email, form.password);

    form.post(route('user-accounts.register', props.student.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            console.log('success');
            emit('verified');
            emit('close');
        },
        onError: (errors) => {
            console.log('errors:', errors);
        },
    });
}

</script>

<template>
  <Transition name="modal">
    <div v-if="show && student" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')" />

      <!-- Panel -->
      <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 space-y-5">

        <!-- Header -->
        <div class="space-y-1">
          <h2 class="text-base font-semibold text-gray-900">
            Create Supabase Account
          </h2>
          <p class="text-sm text-gray-500">
            Set the login credentials for
            <span class="font-medium text-gray-700">{{ student.name }}</span>.
            These will be registered in Supabase Auth.
          </p>
        </div>

        <!-- Email -->
        <div class="space-y-1">
          <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Email
          </label>
          <input v-model="form.email" type="email" placeholder="student@school.edu" autocomplete="off"
            class="w-full px-3 py-2 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2" :class="form.errors.email
              ? 'border-red-300 focus:ring-red-200'
              : 'border-gray-200 focus:border-sidebar focus:ring-sidebar/20'" />
          <p v-if="form.errors.email" class="text-xs text-red-500">
            {{ form.errors.email }}
          </p>
        </div>

        <!-- Password -->
        <div class="space-y-1">
          <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Password
          </label>
          <div class="relative">
            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Min. 8 characters"
              autocomplete="new-password"
              class="w-full pr-10 px-3 py-2 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2"
              :class="form.errors.password
                ? 'border-red-300 focus:ring-red-200'
                : 'border-gray-200 focus:border-sidebar focus:ring-sidebar/20'" />
            <button type="button" tabindex="-1"
              class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
              @click="showPassword = !showPassword">
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                       -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7
                                       a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243
                                       M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29
                                       m7.532 7.532l3.29 3.29M3 3l3.59 3.59
                                       m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7
                                       a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
          <p v-if="form.errors.password" class="text-xs text-red-500">
            {{ form.errors.password }}
          </p>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1">
          <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            Confirm Password
          </label>
          <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'"
            placeholder="Repeat password" autocomplete="new-password"
            class="w-full px-3 py-2 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2" :class="form.errors.password_confirmation
              ? 'border-red-300 focus:ring-red-200'
              : 'border-gray-200 focus:border-sidebar focus:ring-sidebar/20'" />
          <p v-if="form.errors.password_confirmation" class="text-xs text-red-500">
            {{ form.errors.password_confirmation }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2 pt-1">
          <button type="button"
            class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
            :disabled="form.processing" @click="emit('close')">
            Cancel
          </button>
          <button type="button"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-sidebar text-white hover:bg-sidebar/90 transition-colors disabled:opacity-60"
            :disabled="form.processing" @click="submit">
            <span v-if="form.processing">Creating…</span>
            <span v-else>Create & Verify</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 150ms ease, transform 150ms ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
</style>