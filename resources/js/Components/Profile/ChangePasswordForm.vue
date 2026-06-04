<script setup lang="ts">
import { ref } from 'vue';
import SettingsSection from '@/Components/UI/SettingsSection.vue';
import FormField from '@/Components/UI/FormField.vue';
import SaveFeedback from '@/Components/UI/SaveFeedback.vue';
import type { PasswordForm } from '@/types';

const emit = defineEmits<{ submit: [form: PasswordForm] }>();

const form = ref<PasswordForm>({ current: '', newPass: '', confirm: '' });
const saved = ref(false);

function submit() {
    if (!form.value.current || !form.value.newPass) return;
    emit('submit', { ...form.value });
    saved.value = true;
    form.value = { current: '', newPass: '', confirm: '' };
    setTimeout(() => (saved.value = false), 2500);
}
</script>

<template>
    <SettingsSection title="Change Password" description="Choose a strong password">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <FormField label="Current Password" v-model="form.current" type="password" placeholder="••••••••" />
            <FormField label="New Password" v-model="form.newPass" type="password" placeholder="••••••••" />
            <FormField label="Confirm Password" v-model="form.confirm" type="password" placeholder="••••••••" />
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="button" @click="submit"
                class="px-5 py-2.5 text-sm font-semibold bg-sidebar text-white rounded-xl hover:bg-primary-hover transition-colors">
                Update Password
            </button>
            <SaveFeedback :show="saved" message="Password updated!" />
        </div>
    </SettingsSection>
</template>
