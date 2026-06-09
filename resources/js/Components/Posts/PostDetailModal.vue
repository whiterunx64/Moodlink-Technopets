<script setup lang="ts">
import type { Post } from '@/types';

defineProps<{
    post: Post | null;
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    'toggle-flag': [];
}>();

const MOOD_BADGE: Record<string, { pill: string; dot: string }> = {
    Excited:  { pill: 'bg-amber-50 text-amber-600 border border-amber-200',    dot: 'bg-amber-400' },
    Content:  { pill: 'bg-green-50 text-green-700 border border-green-200',    dot: 'bg-green-500' },
    Stressed: { pill: 'bg-orange-50 text-orange-600 border border-orange-200', dot: 'bg-orange-400' },
    Drained:  { pill: 'bg-purple-50 text-purple-600 border border-purple-200', dot: 'bg-purple-400' },
};

function moodStyle(mood: string) {
    return MOOD_BADGE[mood] ?? { pill: 'bg-gray-100 text-gray-600 border border-gray-200', dot: 'bg-gray-400' };
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show && post" class="fixed inset-0 z-50 flex items-center justify-center p-4">

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')" />

                <!-- Modal card -->
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg mx-auto">

                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-border-light">
                        <h3 class="text-base font-bold text-text-primary">Post Detail</h3>
                        <button type="button"
                            class="w-7 h-7 flex items-center justify-center rounded-full text-text-muted hover:bg-hover-soft transition-colors"
                            @click="emit('close')">
                            <i class="fas fa-times text-sm" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5 space-y-5">

                        <!-- Author row -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-sidebar flex items-center justify-center text-base font-bold text-white shrink-0">
                                    {{ (post.anonymous_name ?? 'U').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-text-primary">
                                        {{ post.anonymous_name ?? 'Anonymous (Not Set)' }}
                                    </p>
                                    <p class="text-xs text-text-muted">Section {{ post.section }}</p>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-col items-end gap-1.5 shrink-0">
                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', moodStyle(post.mood).pill]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', moodStyle(post.mood).dot]" />
                                    {{ post.mood }}
                                </span>
                                <span :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                    post.status === 'flagged'
                                        ? 'bg-status-flagged-bg text-status-flagged border border-red-100'
                                        : 'bg-status-safe-bg text-status-safe border border-green-200',
                                ]">
                                    <i :class="['fas text-[10px]', post.status === 'flagged' ? 'fa-flag' : 'fa-check']" />
                                    {{ post.status === 'flagged' ? 'Flagged' : 'Safe' }}
                                </span>
                            </div>
                        </div>

                        <!-- Info boxes -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-xl px-4 py-3">
                                <p class="text-[10px] font-semibold text-text-muted uppercase tracking-wider mb-1">Section</p>
                                <p class="text-sm font-bold text-text-primary">{{ post.section }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-4 py-3">
                                <p class="text-[10px] font-semibold text-text-muted uppercase tracking-wider mb-1">Mood</p>
                                <p class="text-sm font-bold text-text-primary">{{ post.mood }}</p>
                            </div>
                        </div>

                        <!-- Posted time -->
                        <div class="flex items-center gap-2 text-xs text-text-muted">
                            <i class="far fa-clock" />
                            <span>Posted {{ post.date }} at {{ post.time }}</span>
                        </div>

                        <!-- Post content -->
                        <div class="bg-gray-50 rounded-xl px-4 py-4">
                            <p class="text-[10px] font-semibold text-text-muted uppercase tracking-wider mb-2">Post Content</p>
                            <p class="text-sm text-text-secondary leading-relaxed">
                                {{ post.content ?? 'No content provided.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 pb-5">
                        <button type="button" :class="[
                            'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold border transition-all',
                            post.status === 'flagged'
                                ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                                : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
                        ]" @click="emit('toggle-flag')">
                            <i :class="['fas text-xs', post.status === 'flagged' ? 'fa-check' : 'fa-flag']" />
                            {{ post.status === 'flagged' ? 'Clear Flag' : 'Flag Post' }}
                        </button>

                        <button type="button"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-sidebar text-white hover:bg-sidebar/90 transition-all"
                            @click="emit('close')">
                            Close
                        </button>
                    </div>
                </div>

            </div>
        </Transition>
    </Teleport>
</template>
