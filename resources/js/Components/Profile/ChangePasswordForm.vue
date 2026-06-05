<script setup lang="ts">
import { ref } from 'vue';
import SettingsSection from '@/Components/UI/SettingsSection.vue';
import FormField from '@/Components/UI/FormField.vue';
import type { PasswordForm } from '@/types';

withDefaults(defineProps<{
    processing?: boolean;
    errors?: Partial<Record<keyof PasswordForm, string>>;
}>(), {
    processing: false,
    errors: () => ({}),
});

const emit = defineEmits<{ submit: [form: PasswordForm] }>();

const form = ref<PasswordForm>({ current: '', newPass: '', confirm: '' });

function submit() {
    emit('submit', { ...form.value });
}

// Allow the parent to clear inputs after a successful submit.
function reset() {
    form.value = { current: '', newPass: '', confirm: '' };
}

defineExpose({ reset });
</script>

<template>
    <SettingsSection title="Change Password" description="Choose a strong password">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <FormField label="Current Password" v-model="form.current" type="password" placeholder="••••••••"
                :error="errors.current" :show-error-text="false" />
            <FormField label="New Password" v-model="form.newPass" type="password" placeholder="••••••••"
                :error="errors.newPass" :show-error-text="false" />
            <FormField label="Confirm Password" v-model="form.confirm" type="password" placeholder="••••••••"
                :error="errors.confirm" :show-error-text="false" />
        </div>

        <div class="pt-1">
            <button type="button" @click="submit" :disabled="processing"
                class="px-5 py-2.5 text-sm font-semibold bg-sidebar text-white rounded-xl hover:bg-primary-hover transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                Update Password
            </button>
        </div>
    </SettingsSection>
</template>
