<script setup lang="ts">
defineProps<{
    modelValue: string;
    placeholder?: string;
    onSearch?: () => void;
}>();

defineEmits<{
    'update:modelValue': [value: string];
    'search': [];
}>();
</script>

<template>
    <div class="relative flex-1 min-w-0 max-w-none flex sm:min-w-52 sm:max-w-sm">
        <input :value="modelValue" @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            @keyup.enter="$emit('search')" type="text" :placeholder="placeholder"
            class="w-full pl-5 pr-4 py-2.5 text-sm rounded-l-xl border border-r-0 border-gray-200 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors" />
        <button type="button" @click="$emit('search')"
            class="inline-flex items-center justify-center px-5 rounded-r-xl bg-search-button text-white hover:bg-search-button-hover active:bg-search-button-active active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-search-button/40 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
        </button>
    </div>
</template>