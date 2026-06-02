<script setup lang="ts">
import type { Post } from '@/types';

defineProps<{ post: Post }>();
defineEmits<{ 'toggle-flag': []; view: [] }>();

const MOOD_EMOJI: Record<string, string> = {
    happy:    '😊',
    sad:      '😢',
    anxious:  '😰',
    neutral:  '😐',
};
</script>

<template>
    <div :class="[
        'bg-white rounded-2xl border shadow-sm p-5 flex flex-col gap-3 transition-all hover:shadow-md',
        post.status === 'flagged' ? 'border-status-flagged-bg' : 'border-border-light',
    ]">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-hover-soft text-text-primary">
                    {{ (post.anonymous_name ?? 'Unknown').charAt(0) }}
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

            <span class="text-xl leading-none shrink-0">
                {{ MOOD_EMOJI[post.mood?.toLowerCase() ?? ''] ?? '😶' }}
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
                <i :class="[
                    'fas text-[10px] mr-1',
                    post.status === 'flagged' ? 'fa-flag' : 'fa-check',
                ]"></i>

                {{ post.status === 'flagged' ? 'Flagged' : 'Safe' }}
            </span>

            <!-- Toggle flag button -->
            <div class="flex items-center gap-1.5">
                <button @click="$emit('toggle-flag')" :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                    post.status === 'flagged'
                        ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white hover:border-status-safe'
                        : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white hover:border-status-flagged',
                ]">
                    {{ post.status === 'flagged' ? 'Clear' : 'Flag' }}
                </button>

                <!-- View button -->
                <button @click="$emit('view')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-border-light text-text-secondary hover:bg-hover-soft transition-all">
                    View
                </button>
            </div>
        </div>
    </div>
</template>
