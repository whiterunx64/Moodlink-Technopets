<script setup lang="ts">
defineProps<{
    label: string;
    modelValue: string;
    type?: string;
    placeholder?: string;
}>();

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div class="space-y-1.5">
        <label class="text-xs font-semibold text-text-secondary uppercase tracking-wide">
            {{ label }}
        </label>
        <input :type="type ?? 'text'" :value="modelValue" :placeholder="placeholder"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-border-light rounded-xl focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition" />
    </div>
</template>
