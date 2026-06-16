<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(defineProps<{
    firstName: string;
    lastName: string;
    role: string;
    status: string;
    avatarUrl?: string | null;
    uploading?: boolean;
}>(), {
    avatarUrl: null,
    uploading: false,
});

const emit = defineEmits<{ 'change-avatar': [file: File] }>();

const fileInput = ref<HTMLInputElement>();

const initials = computed(() =>
    `${props.firstName.charAt(0)}${props.lastName.charAt(0)}`.toUpperCase() || '?',
);

const isActive = computed(() => props.status === 'active');

const statusLabel = computed(() =>
    props.status.charAt(0).toUpperCase() + props.status.slice(1),
);

function pickAvatar() {
    fileInput.value?.click();
}

function onAvatarSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        emit('change-avatar', file);
    }

    // Reset so selecting the same file again still fires a change event.
    target.value = '';
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm">
        <div class="h-24 bg-gradient-green rounded-t-2xl" />
        <div class="px-6 pb-6 -mt-8">
            <div class="flex items-end justify-between gap-4">
                <div class="relative group shrink-0">
                    <div
                        class="w-16 h-16 rounded-2xl bg-white border-4 border-white shadow-md flex items-center justify-center text-2xl font-extrabold text-sidebar overflow-hidden">
                        <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar" class="w-full h-full object-cover" />
                        <span v-else>{{ initials }}</span>
                    </div>

                    <button type="button" @click="pickAvatar" :disabled="uploading"
                        class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/50 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 transition-opacity disabled:cursor-not-allowed"
                        :class="{ 'opacity-100': uploading }">
                        {{ uploading ? '…' : 'Edit' }}
                    </button>

                    <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                        @change="onAvatarSelected" />
                </div>
                <span class="mb-1 px-3 py-1 text-xs font-semibold rounded-full shrink-0"
                    :class="isActive ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500'">
                    {{ statusLabel }}
                </span>
            </div>
            <div class="mt-3">
                <h2 class="text-lg font-bold text-text-primary">{{ firstName }} {{ lastName }}</h2>
                <p class="text-sm text-text-muted mt-0.5">{{ role }}</p>
            </div>
        </div>
    </div>
</template>
