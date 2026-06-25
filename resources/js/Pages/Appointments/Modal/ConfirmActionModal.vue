<script setup lang="ts">
import { CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

defineProps<{
  type: 'approve' | 'reject';
  name: string;
}>();

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="emit('cancel')" />

      <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
        <div :class="[
          'mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full',
          type === 'approve' ? 'bg-green-100' : 'bg-red-100',
        ]">
          <CheckCircleIcon v-if="type === 'approve'" class="h-6 w-6 text-green-600" />
          <ExclamationCircleIcon v-else class="h-6 w-6 text-red-500" />
        </div>

        <h3 class="text-text-primary mb-1 text-center text-base font-bold">
          {{ type === 'approve' ? 'Approve Appointment?' : 'Reject Appointment?' }}
        </h3>

        <p class="text-text-muted mb-6 text-center text-sm">
          {{
            type === 'approve'
              ? `Confirm the appointment request from ${name}.`
              : `Reject the request from ${name}. This cannot be undone.`
          }}
        </p>

        <div class="flex items-center gap-3">
          <button type="button"
            class="border-border-light text-text-secondary flex-1 rounded-xl border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
            @click="emit('cancel')">
            Cancel
          </button>
          <button type="button" :class="[
            'flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors',
            type === 'approve'
              ? 'bg-sidebar hover:bg-sidebar/90'
              : 'bg-red-500 hover:bg-red-600',
          ]" @click="emit('confirm')">
            {{ type === 'approve' ? 'Approve' : 'Reject' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
