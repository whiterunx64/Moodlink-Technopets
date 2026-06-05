<script setup lang="ts">
withDefaults(defineProps<{
    label: string;
    modelValue: string;
    type?: string;
    placeholder?: string;
    error?: string;
    // When false, only the red border is shown (no inline message text).
    showErrorText?: boolean;
}>(), {
    showErrorText: true,
});

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
            class="w-full px-4 py-2.5 text-sm bg-gray-50 border rounded-xl focus:outline-none focus:ring-2 transition"
            :class="error
                ? 'border-red-400 focus:ring-red-200 focus:border-red-400'
                : 'border-border-light focus:ring-sidebar/20 focus:border-sidebar'" />
        <p v-if="error && showErrorText" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
