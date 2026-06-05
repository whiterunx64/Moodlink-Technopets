<script setup lang="ts">
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';

defineProps<{
    show: boolean;
    processing: boolean;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [];
}>();

function close() {
    emit('close');
}

function confirm() {
    emit('confirm');
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

                        <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-sidebar/10">
                            <ShieldCheckIcon class="w-5 h-5 text-sidebar" />
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 cursor-default">
                                Change Password
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 cursor-default">
                                Are you sure you want to change your password? You may need to sign in again
                                afterwards.
                            </p>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end gap-3">

                        <!-- Cancel -->
                        <button type="button" @click="close"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                            Cancel
                        </button>

                        <!-- Confirm -->
                        <button type="button" @click="confirm" :disabled="processing"
                            :class="{ 'opacity-50 cursor-auto': processing }"
                            class="px-4 py-2 text-sm font-semibold text-white bg-sidebar rounded-xl hover:bg-primary-hover transition-colors">
                            {{ processing ? 'Updating…' : 'Yes, Change Password' }}
                        </button>

                    </div>

                </div>
            </div>

        </Transition>
    </Teleport>
</template>
