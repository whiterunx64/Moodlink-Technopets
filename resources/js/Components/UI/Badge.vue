<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    /** Tailwind classes for the badge background + text colour. */
    variant: string;
    icon?: Component;
}>();
</script>

<template>
    <span
        :class="[
            'inline-flex w-fit items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
            variant,
        ]"
    >
        <component :is="icon" v-if="icon" class="w-3 h-3" />
        {{ label }}
    </span>
</template>
