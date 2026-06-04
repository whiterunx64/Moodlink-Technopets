<script setup lang="ts">
defineProps<{
    modelValue: boolean;
}>();

defineEmits<{
    'update:modelValue': [value: boolean];
}>();
</script>

<template>
    <button type="button" @click="$emit('update:modelValue', !modelValue)" :class="[
        'relative w-11 h-6 rounded-full transition-colors duration-200 shrink-0',
        modelValue ? 'bg-sidebar' : 'bg-gray-200',
    ]">
        <span :class="[
            'absolute top-1 w-4 h-4 rounded-full bg-white shadow transition-all duration-200',
            modelValue ? 'left-6' : 'left-1',
        ]" />
    </button>
</template>
