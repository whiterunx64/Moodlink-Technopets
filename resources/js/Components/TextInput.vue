<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
    type?: string;
    name?: string;
    id?: string;
    placeholder?: string;
    disabled?: boolean;
    error?: boolean;
}>();

const model = defineModel<string>({ required: true });
const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input ref="input" :type="type ?? 'text'" :name="name" :id="id ?? name" :placeholder="placeholder"
        :disabled="disabled" v-model="model" :class="[
            'bg-auth-input-bg text-auth-input-text placeholder-auth-placeholder w-full rounded-xl border px-4 py-3 text-sm transition-colors duration-150 focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 sm:py-4 sm:text-base',
            error
                ? 'border-login-error-text focus:ring-login-error-text/25'
                : 'border-auth-input-border focus:border-ml-btn focus:ring-auth-ring',
        ]" />
</template>
