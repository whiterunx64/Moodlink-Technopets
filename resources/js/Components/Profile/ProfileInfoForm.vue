<script setup lang="ts">
import { ref } from 'vue';
import SettingsSection from '@/Components/UI/SettingsSection.vue';
import FormField from '@/Components/UI/FormField.vue';
import SaveFeedback from '@/Components/UI/SaveFeedback.vue';
import type { AdminProfile } from '@/types';

const props = defineProps<{ profile: AdminProfile }>();
const emit = defineEmits<{ save: [profile: AdminProfile] }>();

const form = ref<AdminProfile>({ ...props.profile });
const saved = ref(false);

function save() {
    emit('save', { ...form.value });
    saved.value = true;
    setTimeout(() => (saved.value = false), 2500);
}
</script>

<template>
    <SettingsSection title="Profile Information" description="Update your personal details">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <FormField label="First Name" v-model="form.firstName" />
            <FormField label="Last Name" v-model="form.lastName" />
            <FormField label="Email Address" v-model="form.email" type="email" />
            <FormField label="Phone Number" v-model="form.phone" type="tel" />
            <FormField label="Role" v-model="form.role" />
            <FormField label="Department" v-model="form.department" />
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="button" @click="save"
                class="px-5 py-2.5 text-sm font-semibold bg-sidebar text-white rounded-xl hover:bg-primary-hover transition-colors">
                Save Changes
            </button>
            <SaveFeedback :show="saved" />
        </div>
    </SettingsSection>
</template>
