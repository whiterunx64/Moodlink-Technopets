<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit';
    disabled?: boolean;
}>();
</script>

<template>
    <button :type="type ?? 'button'" :disabled="disabled"
        class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-ml-btn px-4 py-3 text-sm font-bold text-white transition-colors duration-200 hover:bg-ml-btn-hover focus:ring-2 focus:ring-ml-btn focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 sm:py-4 sm:text-base">
        <slot />
    </button>
</template>