<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const PRESETS = [
    { label: 'Full Day', times: TIME_SLOTS.map((s) => s.value) },
    { label: 'Morning', times: ['08:00', '09:00', '10:00', '11:00', '12:00'] },
    {
        label: 'Afternoon',
        times: ['13:00', '14:00', '15:00', '16:00', '17:00'],
    },
];

const todayISO = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});

const emit = defineEmits<{
    close: [];
    saved: [];
}>();

const form = useForm({ date: '', start_times: [] as string[] });

function toggleTime(value: string) {
    const idx = form.start_times.indexOf(value);
    if (idx === -1) form.start_times.push(value);
    else form.start_times.splice(idx, 1);
}

function applyPreset(times: string[]) {
    const allSelected = times.every((t) => form.start_times.includes(t));
    if (allSelected) {
        form.start_times = form.start_times.filter((t) => !times.includes(t));
    } else {
        const merged = new Set([...form.start_times, ...times]);
        form.start_times = TIME_SLOTS.map((s) => s.value).filter((v) =>
            merged.has(v),
        );
    }
}

function isPresetActive(times: string[]) {
    return times.every((t) => form.start_times.includes(t));
}

const selectedCount = computed(() => form.start_times.length);

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
            <div
                class="absolute inset-0 bg-black/30 backdrop-blur-sm"
                @click="close"
            />

            <div class="relative w-full max-w-sm bg-white p-7 shadow-xl">
                <button
                    type="button"
                    class="text-text-muted absolute top-4 right-4 p-1 transition-colors hover:bg-gray-100"
                    @click="close"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>

                <h2
                    class="text-text-primary mb-1 text-center text-base font-bold"
                >
                    Add Available Slots
                </h2>
                <p class="mb-5 text-center text-xs text-gray-400">
                    GCU Operating Hours: 8:00 AM – 6:00 PM
                </p>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label
                            class="text-text-secondary mb-1.5 block text-xs font-medium"
                        >
                            Date
                        </label>
                        <input
                            v-model="form.date"
                            type="date"
                            required
                            :min="todayISO"
                            class="border-border-light focus:ring-sidebar/30 focus:border-sidebar text-text-primary w-full border px-4 py-2.5 text-sm focus:ring-2 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.date"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.date }}
                        </p>
                    </div>

                    <!-- Quick presets -->
                    <div>
                        <label
                            class="text-text-secondary mb-2 block text-xs font-medium"
                        >
                            Quick Select
                        </label>
                        <div class="flex gap-2">
                            <button
                                v-for="preset in PRESETS"
                                :key="preset.label"
                                type="button"
                                :class="[
                                    'flex-1 border py-1.5 text-xs font-semibold transition-colors',
                                    isPresetActive(preset.times)
                                        ? 'bg-sidebar border-sidebar text-white'
                                        : 'border-border-light text-text-secondary hover:border-sidebar/40 hover:bg-sidebar/5',
                                ]"
                                @click="applyPreset(preset.times)"
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Individual time slots -->
                    <div>
                        <label
                            class="text-text-secondary mb-2 block text-xs font-medium"
                        >
                            Start Times
                            <span
                                v-if="selectedCount > 0"
                                class="text-sidebar ml-2 font-semibold"
                            >
                                &middot; {{ selectedCount }} selected
                            </span>
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                v-for="slot in TIME_SLOTS"
                                :key="slot.value"
                                type="button"
                                :class="[
                                    'border py-2 text-xs font-medium transition-colors',
                                    form.start_times.includes(slot.value)
                                        ? 'bg-sidebar border-sidebar text-white'
                                        : 'border-border-light text-text-secondary hover:border-sidebar/40 hover:bg-sidebar/5 bg-white',
                                ]"
                                @click="toggleTime(slot.value)"
                            >
                                {{ slot.label }}
                            </button>
                        </div>
                        <p
                            v-if="form.errors.start_times"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.start_times }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            class="border-border-light text-text-secondary flex-1 border py-2.5 text-sm font-medium transition-colors hover:bg-gray-50"
                            @click="close"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="
                                form.processing ||
                                selectedCount === 0 ||
                                !form.date
                            "
                            class="bg-sidebar hover:bg-sidebar/90 flex-1 py-2.5 text-sm font-semibold text-white transition-colors disabled:opacity-60"
                        >
                            <span v-if="form.processing">Saving…</span>
                            <span v-else-if="selectedCount === 0"
                                >Save Slots</span
                            >
                            <span v-else
                                >Save {{ selectedCount }}
                                {{
                                    selectedCount === 1 ? 'Slot' : 'Slots'
                                }}</span
                            >
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
