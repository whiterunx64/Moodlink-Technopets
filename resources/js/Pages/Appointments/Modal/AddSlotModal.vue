<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const TIME_SLOTS = [
  { value: '08:00', label: '8:00 AM' },
  { value: '09:00', label: '9:00 AM' },
  { value: '10:00', label: '10:00 AM' },
  { value: '11:00', label: '11:00 AM' },
  { value: '12:00', label: '12:00 PM' },
  { value: '13:00', label: '1:00 PM' },
  { value: '14:00', label: '2:00 PM' },
  { value: '15:00', label: '3:00 PM' },
  { value: '16:00', label: '4:00 PM' },
  { value: '17:00', label: '5:00 PM' },
  { value: '18:00', label: '6:00 PM' },
];

const todayISO = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });

const emit = defineEmits<{
  close: [];
  saved: [];
}>();

const form = useForm({ date: '', start_time: '' });

function submit() {
  form.post(route('appointments.slots.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      emit('saved');
    },
  });
}

function close() {
  form.reset();
  emit('close');
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="close" />

      <div class="relative w-full max-w-sm rounded-2xl bg-white p-7 shadow-xl">
        <button type="button"
          class="text-text-muted absolute top-4 right-4 rounded-full p-1 transition-colors hover:bg-gray-100"
          @click="close">
          <XMarkIcon class="h-4 w-4" />
        </button>

        <h2 class="text-text-primary mb-1 text-center text-base font-bold">
          Add Available Slot
        </h2>
        <p class="mb-5 text-center text-xs text-gray-400">
          GCU Operating Hours: 8:00 AM – 6:00 PM
        </p>

        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="text-text-secondary mb-1.5 block text-xs font-medium">
              Date
            </label>
            <input v-model="form.date" type="date" required :min="todayISO"
              class="border-border-light focus:ring-sidebar/30 focus:border-sidebar text-text-primary w-full rounded-xl border px-4 py-2.5 text-sm focus:ring-2 focus:outline-none" />
            <p v-if="form.errors.date" class="mt-1 text-xs text-red-500">
              {{ form.errors.date }}
            </p>
          </div>

          <div>
            <label class="text-text-secondary mb-2 block text-xs font-medium">
              Start Time
              <span v-if="form.start_time" class="ml-2 font-semibold text-sidebar">
                &middot; {{TIME_SLOTS.find((s) => s.value === form.start_time)?.label}}
              </span>
            </label>
            <div class="grid grid-cols-4 gap-2">
              <button v-for="slot in TIME_SLOTS" :key="slot.value" type="button" :class="[
                'rounded-lg border py-2 text-xs font-medium transition-colors',
                form.start_time === slot.value
                  ? 'bg-sidebar border-sidebar text-white'
                  : 'border-border-light bg-white text-text-secondary hover:border-sidebar/40 hover:bg-sidebar/5',
              ]" @click="form.start_time = slot.value">
                {{ slot.label }}
              </button>
            </div>
            <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-500">
              {{ form.errors.start_time }}
            </p>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button type="button"
              class="border-border-light text-text-secondary flex-1 rounded-xl border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
              @click="close">
              Cancel
            </button>
            <button type="submit" :disabled="form.processing || !form.start_time"
              class="bg-sidebar hover:bg-sidebar/90 flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60">
              {{ form.processing ? 'Saving…' : 'Save Slot' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
