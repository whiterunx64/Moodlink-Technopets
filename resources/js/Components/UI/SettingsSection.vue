<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    danger?: boolean;
}>();
</script>

<template>
    <div :class="[
        'bg-white rounded-2xl shadow-sm p-6 space-y-5',
        danger ? 'border border-red-100' : 'border border-border-light',
    ]">
        <div>
            <h3 :class="['text-base font-semibold', danger ? 'text-red-600' : 'text-text-primary']">
                {{ title }}
            </h3>
            <p v-if="description" class="text-xs text-text-muted mt-0.5">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>
