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
    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show && post" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')" />

                <!-- Modal card -->
                <div class="relative mx-auto w-full max-w-lg rounded-2xl bg-white shadow-xl">
                    <!-- Modal header -->
                    <div class="border-border-light flex items-center justify-between border-b px-6 pt-5 pb-4">
                        <h3 class="text-text-primary text-base font-bold">
                            Post Detail
                        </h3>
                        <button type="button"
                            class="text-text-muted hover:bg-hover-soft flex h-7 w-7 items-center justify-center rounded-full transition-colors"
                            @click="emit('close')">
                            <i class="fas fa-times text-sm" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="space-y-5 px-6 py-5">
                        <!-- Author row -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="bg-sidebar flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-bold text-white">
                                    {{
                                        (post.anonymous_name ?? 'U')
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </div>
                                <div>
                                    <p class="text-text-primary text-sm font-semibold capitalize">
                                        {{
                                            post.first_name +
                                            ' ' +
                                            post.last_name
                                        }}
                                    </p>
                                    <p class="text-text-muted text-xs">
                                        {{ post.anonymous_name ?? 'Anonymous' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                    moodStyle(post.mood).pill,
                                ]">
                                    <span :class="[
                                        'h-1.5 w-1.5 rounded-full',
                                        moodStyle(post.mood).dot,
                                    ]" />
                                    {{ post.mood }}
                                </span>
                                <span :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                    post.status === 'flagged'
                                        ? 'bg-status-flagged-bg text-status-flagged border border-red-100'
                                        : 'bg-status-safe-bg text-status-safe border border-green-200',
                                ]">
                                    <i :class="[
                                        'fas text-[10px]',
                                        post.status === 'flagged'
                                            ? 'fa-flag'
                                            : 'fa-check',
                                    ]" />
                                    {{
                                        post.status === 'flagged'
                                            ? 'Flagged'
                                            : 'Safe'
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Info boxes -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-gray-50 px-4 py-3">
                                <p class="text-text-muted mb-1 text-[10px] font-semibold tracking-wider uppercase">
                                    Section
                                </p>
                                <p class="text-text-primary text-sm font-bold">
                                    {{ post.section }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-4 py-3">
                                <p class="text-text-muted mb-1 text-[10px] font-semibold tracking-wider uppercase">
                                    Mood
                                </p>
                                <p class="text-text-primary text-sm font-bold">
                                    {{ post.mood }}
                                </p>
                            </div>
                        </div>

                        <!-- Posted time -->
                        <div class="text-text-muted flex items-center gap-2 text-xs">
                            <i class="far fa-clock" />
                            <span>Posted {{ post.date }} at {{ post.time }}</span>
                        </div>

                        <!-- Post content -->
                        <div class="rounded-xl bg-gray-50 px-4 py-4">
                            <p class="text-text-muted mb-2 text-[10px] font-semibold tracking-wider uppercase">
                                Post Content
                            </p>
                            <p class="text-text-secondary text-sm leading-relaxed">
                                {{ post.content ?? 'No content provided.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 pb-5">
                        <button type="button" :class="[
                            'inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-semibold transition-all',
                            post.status === 'flagged'
                                ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                                : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
                        ]" @click="emit('toggle-flag')">
                            <i :class="[
                                'fas text-xs',
                                post.status === 'flagged'
                                    ? 'fa-check'
                                    : 'fa-flag',
                            ]" />
                            {{
                                post.status === 'flagged'
                                    ? 'Clear Flag'
                                    : 'Flag Post'
                            }}
                        </button>

                        <button type="button"
                            class="bg-sidebar hover:bg-sidebar/90 rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all"
                            @click="emit('close')">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
