<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

withDefaults(defineProps<{
    currentPage: number;
    totalPages: number;
    pageNumbers: (number | '…')[];
    rangeStart: number;
    rangeEnd: number;
    total: number;
    fixed?: boolean;
}>(), { fixed: false });

const emit = defineEmits<{
    'update:currentPage': [page: number];
    prev: [];
    next: [];
}>();
</script>

<template>
    <div :class="fixed
        ? 'fixed bottom-0 left-0 right-0 lg:left-64 z-20 bg-white border-t border-border-light flex items-center justify-between px-4 sm:px-6 py-3'
        : 'shrink-0 flex items-center justify-between px-5 py-3 border-t border-border-light bg-white'
    ">
        <p class="text-xs text-text-muted">
            Showing
            <span class="font-semibold text-text-secondary">{{ rangeStart }}</span>–<span
                class="font-semibold text-text-secondary"
                >{{ rangeEnd }}</span
            >
            of <span class="font-semibold text-text-secondary">{{ total }}</span>
        </p>

        <div class="flex items-center gap-1">
            <button
                @click="emit('prev')"
                :disabled="currentPage === 1"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-border-light text-text-muted hover:bg-bg-surface disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            >
                <ChevronLeftIcon class="w-3 h-3" />
            </button>

            <template v-for="(p, i) in pageNumbers" :key="`${p}-${i}`">
                <span
                    v-if="p === '…'"
                    class="w-8 h-8 flex items-center justify-center text-xs text-text-muted"
                    >…</span
                >
                <button
                    v-else
                    @click="emit('update:currentPage', p as number)"
                    :class="[
                        'w-8 h-8 flex items-center justify-center rounded-lg border text-xs font-semibold transition-colors',
                        currentPage === p
                            ? 'bg-sidebar text-white border-sidebar'
                            : 'border-border-light text-text-secondary hover:bg-bg-surface',
                    ]"
                >
                    {{ p }}
                </button>
            </template>

            <button
                @click="emit('next')"
                :disabled="currentPage === totalPages"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-border-light text-text-muted hover:bg-bg-surface disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            >
                <ChevronRightIcon class="w-3 h-3" />
            </button>
        </div>
    </div>
</template>
