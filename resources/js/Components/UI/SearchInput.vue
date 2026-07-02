<script setup lang="ts">
defineProps<{
    modelValue: string;
    placeholder?: string;
    onSearch?: () => void;
}>();

defineEmits<{
    'update:modelValue': [value: string];
    search: [];
}>();
</script>

<template>
    <div
        class="relative flex max-w-none min-w-0 flex-1 sm:max-w-sm sm:min-w-52"
    >
        <input
            :value="modelValue"
            @input="
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
            @keyup.enter="$emit('search')"
            type="text"
            :placeholder="placeholder"
            class="focus:ring-sidebar/20 focus:border-sidebar w-full border border-r-0 border-gray-200 bg-white py-2.5 pr-4 pl-5 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:ring-2 focus:outline-none"
        />
        <button
            type="button"
            @click="$emit('search')"
            class="bg-search-button hover:bg-search-button-hover active:bg-search-button-active focus:ring-search-button/40 inline-flex shrink-0 items-center justify-center px-5 text-white transition-all focus:ring-2 focus:outline-none active:scale-95"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
        </button>
    </div>
</template>
