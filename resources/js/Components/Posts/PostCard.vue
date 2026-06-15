<script setup lang="ts">
import type { Post } from '@/types';

defineProps<{ post: Post }>();
defineEmits<{ 'toggle-flag': []; view: [] }>();

const MOOD_BADGE: Record<string, { pill: string; dot: string }> = {
    Excited:  { pill: 'bg-amber-50 text-amber-600 border border-amber-200',   dot: 'bg-amber-400' },
    Content:  { pill: 'bg-green-50 text-green-700 border border-green-200',   dot: 'bg-green-500' },
    Stressed: { pill: 'bg-orange-50 text-orange-600 border border-orange-200', dot: 'bg-orange-400' },
    Drained:  { pill: 'bg-purple-50 text-purple-600 border border-purple-200', dot: 'bg-purple-400' },
};

function moodStyle(mood: string) {
    return MOOD_BADGE[mood] ?? { pill: 'bg-gray-100 text-gray-600 border border-gray-200', dot: 'bg-gray-400' };
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm p-5 flex flex-col gap-3 transition-all hover:shadow-md">

        <!-- Header -->
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-hover-soft text-text-primary">
                    {{ (post.anonymous_name ?? 'U').charAt(0) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-text-primary leading-tight">
                        {{ post.anonymous_name ?? 'Anonymous (Not Set)' }}
                    </p>
                    <p class="text-xs text-text-muted">
                        {{ post.section }} · {{ post.date }}, {{ post.time }}
                    </p>
                </div>
            </div>

            <!-- Mood badge -->
            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium shrink-0', moodStyle(post.mood).pill]">
                <span :class="['w-1.5 h-1.5 rounded-full', moodStyle(post.mood).dot]" />
                {{ post.mood }}
            </span>
        </div>

        <!-- Content -->
        <p class="text-sm text-text-secondary leading-relaxed line-clamp-3">
            {{ post.content }}
        </p>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-1 border-t border-border-light">
            <span :class="[
                'text-xs font-semibold px-2.5 py-1 rounded-full',
                post.status === 'flagged'
                    ? 'bg-status-flagged-bg text-status-flagged'
                    : 'bg-status-safe-bg text-status-safe',
            ]">
                <i :class="['fas text-[10px] mr-1', post.status === 'flagged' ? 'fa-flag' : 'fa-check']" />
                {{ post.status === 'flagged' ? 'Flagged' : 'Safe' }}
            </span>

            <div class="flex items-center gap-1.5">
                <button type="button" :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                    post.status === 'flagged'
                        ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                        : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
                ]" @click="$emit('toggle-flag')">
                    {{ post.status === 'flagged' ? 'Clear' : 'Flag' }}
                </button>

                <button type="button"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border-light text-text-secondary hover:bg-hover-soft transition-all"
                    @click="$emit('view')">
                    View
                </button>
            </div>
        </div>
    </div>
</template>
