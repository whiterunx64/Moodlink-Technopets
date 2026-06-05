<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProfileCard from '@/Components/Profile/ProfileCard.vue';
import ProfileInfoForm from '@/Components/Profile/ProfileInfoForm.vue';
import ChangePasswordForm from '@/Components/Profile/ChangePasswordForm.vue';
import NotificationPrefs from '@/Components/Profile/NotificationPrefs.vue';
import DangerZone from '@/Components/Profile/DangerZone.vue';
import DeleteAdminModal from '@/Pages/Profile/Modal/DeleteAdminModal.vue';
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

// --- Delete account ---
const showDeleteModal = ref(false);
const deleteForm = useForm({ password: '' });

function onDeleteAccount() {
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deleteForm.reset();
    deleteForm.clearErrors();
}

function submitDeleteAccount(password: string) {
    deleteForm.password = password;
    deleteForm.delete(route('profile.delete'), {
        preserveScroll: true,
        onError: () => { },
        onFinish: () => deleteForm.reset(),
    });
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

        <DeleteAdminModal :show="showDeleteModal" :processing="deleteForm.processing"
            :password-error="deleteForm.errors.password" @close="closeDeleteModal" @submit="submitDeleteAccount" />
    </AdminLayout>
</template>
