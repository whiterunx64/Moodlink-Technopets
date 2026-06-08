<script setup lang="ts">
import { computed } from 'vue';
import { getInitials, getAvatarColor } from '@/composables/useInitials';

const props = withDefaults(defineProps<{
    name: string;
    /** Use the name to pick a deterministic background colour. */
    colored?: boolean;
    size?: 'sm' | 'md' | 'lg';
}>(), {
    colored: true,
    size: 'md',
});

const SIZES = {
    sm: 'w-7 h-7 text-[10px]',
    md: 'w-8 h-8 text-xs',
    lg: 'w-11 h-11 text-sm',
} as const;

const initials = computed(() => getInitials(props.name));
const bg = computed(() =>
    props.colored ? getAvatarColor(props.name) : 'bg-hover-soft text-text-primary',
);
</script>

<template>
    <div
        :class="[
            'shrink-0 rounded-full flex items-center justify-center font-bold',
            colored ? 'text-white' : '',
            bg,
            SIZES[size],
        ]"
    >
        {{ initials }}
    </div>
</template>
