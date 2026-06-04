<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProfileCard from '@/Components/Profile/ProfileCard.vue';
import ProfileInfoForm from '@/Components/Profile/ProfileInfoForm.vue';
import ChangePasswordForm from '@/Components/Profile/ChangePasswordForm.vue';
import NotificationPrefs from '@/Components/Profile/NotificationPrefs.vue';
import DangerZone from '@/Components/Profile/DangerZone.vue';
import type { AdminProfile, NotificationPreferences, PasswordForm } from '@/types';

const profile = ref<AdminProfile>({
    firstName: 'Admin',
    lastName: 'User',
    email: 'admin@moodlink.edu',
    phone: '+63 912 345 6789',
    role: 'Guidance Counselor',
    department: 'Student Affairs',
});

const notifications = ref<NotificationPreferences>({
    newFlags: true,
    appointments: true,
    escalations: true,
    weeklyReports: false,
    systemUpdates: false,
});

function onSaveProfile(updated: AdminProfile) {
    profile.value = updated;
    // router.patch(route('profile.update'), updated);
}

function onChangePassword(form: PasswordForm) {
    // router.put(route('password.update'), form);
    console.log('password change submitted', form);
}

function onLogout() {
    router.post(route('logout'));
}

function onDeleteAccount() {
    // router.delete(route('profile.destroy'));
}
</script>

<template>
    <AdminLayout title="Settings">
        <div class="max-w-4xl space-y-6">
            <ProfileCard :first-name="profile.firstName" :last-name="profile.lastName" :role="profile.role"
                :department="profile.department" />

            <ProfileInfoForm :profile="profile" @save="onSaveProfile" />

            <ChangePasswordForm @submit="onChangePassword" />

            <NotificationPrefs v-model="notifications" />

            <DangerZone @logout="onLogout" @delete-account="onDeleteAccount" />
        </div>
    </AdminLayout>
</template>
