<script setup lang="ts">
import type { Mood } from '@/types';

withDefaults(
    defineProps<{
        name?: string;
        time?: string;
        message?: string | null;
        flagged?: boolean;
        mood?: Mood | null;
    }>(),
    {
        flagged: false,
        mood: null,
    },
);
</script>

<template>
    <div
        class="group bg-post-card-bg hover:bg-post-card-bg-hover flex shrink-0 gap-3 rounded-xl p-4 transition-all duration-200"
    >
        <!-- Avatar -->
        <div
            class="bg-avatar-bg text-text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold select-none"
        >
            {{ name?.charAt(0) ?? '?' }}
        </div>

        <div class="min-w-0 flex-1">
            <!-- Top row -->
            <div class="mb-1 flex items-center justify-between gap-2">
                <span
                    class="text-text-primary truncate text-sm font-semibold"
                    >{{ name }}</span
                >
                <div class="flex shrink-0 items-center gap-2">
                    <span v-if="mood" class="text-base leading-none">{{
                        moodEmoji[mood]
                    }}</span>
                    <span class="text-text-muted text-xs">{{ time }}</span>
                </div>
            </div>

            <!-- Message -->
            <p class="text-text-muted line-clamp-2 text-sm leading-relaxed">
                {{ message }}
            </p>

            <!-- Badge -->
            <div class="mt-2 flex items-center gap-2">
                <span
                    v-if="flagged"
                    class="bg-flagged-badge-bg text-flagged-badge-text inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                >
                    <i class="fas fa-flag text-[10px]" /> Flagged
                </span>
                <span
                    v-else
                    class="bg-status-safe-bg text-status-safe inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                >
                    <i class="fas fa-check text-[10px]" /> Safe
                </span>
            </div>
        </div>
    </div>
</template>
