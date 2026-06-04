<script setup lang="ts">
defineProps<{
    show: boolean;
    message?: string;
}>();
</script>

<template>
    <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
        <span v-if="show" class="text-sm text-green-600 font-medium flex items-center gap-1">
            <i class="fas fa-check text-xs"></i>
            {{ message ?? 'Saved!' }}
        </span>
    </Transition>
</template>
