<script setup lang="ts">
import type { Post } from '@/types';

defineProps<{ post: Post }>();
defineEmits<{ 'toggle-flag': []; view: [] }>();

const MOOD_BADGE: Record<string, { pill: string; dot: string }> = {
    Excited: {
        pill: 'bg-amber-50 text-amber-600 border border-amber-200',
        dot: 'bg-amber-400',
    },
    Content: {
        pill: 'bg-green-50 text-green-700 border border-green-200',
        dot: 'bg-green-500',
    },
    Stressed: {
        pill: 'bg-orange-50 text-orange-600 border border-orange-200',
        dot: 'bg-orange-400',
    },
    Drained: {
        pill: 'bg-purple-50 text-purple-600 border border-purple-200',
        dot: 'bg-purple-400',
    },
};

function moodStyle(mood: string) {
    return (
        MOOD_BADGE[mood] ?? {
            pill: 'bg-gray-100 text-gray-600 border border-gray-200',
            dot: 'bg-gray-400',
        }
    );
}
</script>

<template>
    <div
        class="border-border-light flex flex-col gap-3 rounded-2xl border bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div
                    class="bg-hover-soft text-text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                    {{ (post.anonymous_name ?? 'U').charAt(0) }}
                </div>
                <div>
                    <p class="text-text-primary text-sm leading-tight font-semibold">
                        {{ post.anonymous_name ?? 'Anonymous (Not Set)' }}
                    </p>
                    <p class="text-text-muted text-xs">
                        {{ post.program }} · {{ post.date }}, {{ post.time }}
                    </p>
                </div>
            </div>

            <!-- Mood badge -->
            <span :class="[
                'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                moodStyle(post.mood).pill,
            ]">
                <span :class="[
                    'h-1.5 w-1.5 rounded-full',
                    moodStyle(post.mood).dot,
                ]" />
                {{ post.mood }}
            </span>
        </div>

        <!-- Content -->
        <p class="text-text-secondary line-clamp-3 text-sm h-18 leading-relaxed">
            {{ post.content }}
        </p>

        <!-- Footer -->
        <div class="border-border-light flex items-center justify-between border-t pt-1">
            <span :class="[
                'rounded-full px-2.5 py-1 text-xs font-semibold',
                post.status === 'flagged'
                    ? 'bg-status-flagged-bg text-status-flagged'
                    : 'bg-status-safe-bg text-status-safe',
            ]">
                <i :class="[
                    'fas mr-1 text-[10px]',
                    post.status === 'flagged' ? 'fa-flag' : 'fa-check',
                ]" />
                {{ post.status === 'flagged' ? 'Flagged' : 'Safe' }}
            </span>

            <div class="flex items-center gap-1.5">
                <button type="button" :class="[
                    'rounded-lg border px-3 py-1.5 text-xs font-medium transition-all',
                    post.status === 'flagged'
                        ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                        : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
                ]" @click="$emit('toggle-flag')">
                    {{ post.status === 'flagged' ? 'Clear' : 'Flag' }}
                </button>

                <button type="button"
                    class="border-border-light text-text-secondary hover:bg-hover-soft rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
                    @click="$emit('view')">
                    View
                </button>
            </div>
        </div>
    </div>
</template>
