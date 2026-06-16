<script setup lang="ts">
import { ref } from 'vue';
import SettingsSection from '@/Components/UI/SettingsSection.vue';
import FormField from '@/Components/UI/FormField.vue';
import type { AdminProfile } from '@/types';

const props = withDefaults(defineProps<{
    profile: AdminProfile;
    processing?: boolean;
    errors?: Partial<Record<'firstName' | 'lastName' | 'phone', string>>;
}>(), {
    processing: false,
    errors: () => ({}),
});

const emit = defineEmits<{ save: [profile: AdminProfile] }>();

const form = ref<AdminProfile>({ ...props.profile });

function save() {
    emit('save', { ...form.value });
}
</script>

<template>
    <SettingsSection title="Profile Information" description="Update your personal details">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <FormField label="First Name" v-model="form.firstName" :error="errors.firstName"
                :show-error-text="false" />
            <FormField label="Last Name" v-model="form.lastName" :error="errors.lastName" :show-error-text="false" />
            <FormField label="Phone Number" v-model="form.phone" type="tel" :error="errors.phone"
                :show-error-text="false" />
        </div>

        <div class="pt-1">
            <button type="button" @click="save" :disabled="processing"
                class="px-5 py-2.5 text-sm font-semibold bg-sidebar text-white rounded-xl hover:bg-primary-hover transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                Save Changes
            </button>
        </div>
    </SettingsSection>
</template>
