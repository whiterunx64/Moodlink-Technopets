<script setup lang="ts">
import { ref, watch } from 'vue';
import { ExclamationTriangleIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    show: boolean;
    processing: boolean;
    passwordError?: string;
}>();

const emit = defineEmits<{
    close: [];
    submit: [password: string];
}>();

const password = ref('');
const passwordInput = ref<HTMLInputElement | null>(null);
const showPassword = ref(false);

watch(() => props.show, (val) => {
    if (val) {
        password.value = '';
        showPassword.value = false;
    }
});

function togglePassword() {
    showPassword.value = !showPassword.value;
}

function close() {
    password.value = '';
    showPassword.value = false;
    emit('close');
}

function submit() {
    emit('submit', password.value);
}
</script>

<template>
    <Teleport to="body">

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">

            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50" @click="close" />

                <!-- Modal -->
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">

                    <!-- Header -->
                    <div class="flex items-start gap-4">

                        <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-red-100">
                            <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 cursor-default">
                                Delete Account
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 cursor-default">
                                This action is permanent and cannot be undone. Enter your password to confirm.
                            </p>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="mt-5">
                        <label for="delete-password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Password
                        </label>

                        <div class="relative">

                            <input id="delete-password" ref="passwordInput" v-model="password"
                                :type="showPassword ? 'text' : 'password'" placeholder="Enter your password"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 pr-10 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-0 focus:bg-gray-100 dark:focus:bg-gray-700 transition-colors"
                                @keyup.enter="submit" />

                            <!-- Eye Button -->
                            <button type="button" @click="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 cursor-pointer transition-transform active:scale-90">
                                <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                                <EyeSlashIcon v-else class="w-5 h-5" />
                            </button>

                        </div>

                        <p v-if="passwordError" class="mt-1.5 text-xs text-red-600">
                            {{ passwordError }}
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end gap-3">

                        <!-- Cancel -->
                        <button type="button" @click="close"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                            Cancel
                        </button>

                        <!-- Delete -->
                        <button type="button" @click="submit" :disabled="processing"
                            :class="{ 'opacity-50 cursor-auto': processing }"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                            {{ processing ? 'Deleting…' : 'Delete Account' }}
                        </button>

                    </div>

                </div>
            </div>

        </Transition>
    </Teleport>
</template>