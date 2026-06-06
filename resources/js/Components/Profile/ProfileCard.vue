<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    firstName: string;
    lastName: string;
    role: string;
    status: string;
}>();

const initials = computed(() =>
    `${props.firstName.charAt(0)}${props.lastName.charAt(0)}`.toUpperCase() || '?',
);

const isActive = computed(() => props.status === 'active');

const statusLabel = computed(() =>
    props.status.charAt(0).toUpperCase() + props.status.slice(1),
);
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm">
        <div class="h-24 bg-gradient-green rounded-t-2xl" />
        <div class="px-6 pb-6 -mt-8">
            <div class="flex items-end justify-between gap-4">
                <div
                    class="w-16 h-16 rounded-2xl bg-white border-4 border-white shadow-md flex items-center justify-center text-2xl font-extrabold text-sidebar shrink-0">
                    {{ initials }}
                </div>
                <span class="mb-1 px-3 py-1 text-xs font-semibold rounded-full shrink-0"
                    :class="isActive ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500'">
                    {{ statusLabel }}
                </span>
            </div>
            <div class="mt-3">
                <h2 class="text-lg font-bold text-text-primary">{{ firstName }} {{ lastName }}</h2>
                <p class="text-sm text-text-muted mt-0.5">{{ role }}</p>
            </div>
        </div>
    </div>
</template>
