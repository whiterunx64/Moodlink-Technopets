<script setup lang="ts">
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div class="relative flex-1 min-w-52 max-w-sm">
        <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 -translate-y-1/2 w-3 h-3 text-text-muted pointer-events-none"
        />
        <input
            :value="modelValue"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            type="text"
            :placeholder="placeholder"
            class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-border-light bg-white text-text-secondary placeholder-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors"
        />
    </div>
</template>
