<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProfileCard from '@/Components/Profile/ProfileCard.vue';
import ProfileInfoForm from '@/Components/Profile/ProfileInfoForm.vue';
import ChangePasswordForm from '@/Components/Profile/ChangePasswordForm.vue';
import NotificationPrefs from '@/Components/Profile/NotificationPrefs.vue';
import DangerZone from '@/Components/Profile/DangerZone.vue';
import DeleteAdminModal from '@/Pages/Profile/Modal/DeleteAdminModal.vue';
import ConfirmPasswordChangeModal from '@/Pages/Profile/Modal/ConfirmPasswordChangeModal.vue';
import { useToast } from '@/composables/useToast';
import type { AdminProfile, NotificationPreferences, PasswordForm } from '@/types';

const { add } = useToast();

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

// --- Change password ---
const changePasswordForm = ref<InstanceType<typeof ChangePasswordForm>>();
const showPasswordModal = ref(false);
const pendingPassword = ref<PasswordForm | null>(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Map server-side validation errors back onto the component's field keys so the
// relevant inputs are highlighted while the message is surfaced via a toast.
const passwordErrors = computed<Partial<Record<keyof PasswordForm, string>>>(() => ({
    current: passwordForm.errors.current_password,
    newPass: passwordForm.errors.password,
}));

// Stash the entered values and ask the user to confirm before submitting.
function onChangePassword(form: PasswordForm) {
    pendingPassword.value = form;
    showPasswordModal.value = true;
}

function closePasswordModal() {
    showPasswordModal.value = false;
    pendingPassword.value = null;
}

function confirmPasswordChange() {
    const form = pendingPassword.value;
    if (form === null) return;

    passwordForm.current_password = form.current;
    passwordForm.password = form.newPass;
    passwordForm.password_confirmation = form.confirm;

    passwordForm.put(route('profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            changePasswordForm.value?.reset();
            add({ type: 'success', message: 'Password updated successfully.' });
        },
        onError: (errors) => {
            const message = errors.current_password ?? errors.password
                ?? 'Unable to update password. Please try again.';
            add({ type: 'error', message });
        },
        onFinish: () => {
            passwordForm.reset();
            closePasswordModal();
        },
    });
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
    deleteForm.delete(route('profile.account.delete'), {
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

            <ChangePasswordForm ref="changePasswordForm" :processing="passwordForm.processing" :errors="passwordErrors"
                @submit="onChangePassword" />

            <NotificationPrefs v-model="notifications" />

            <DangerZone @logout="onLogout" @delete-account="onDeleteAccount" />
        </div>

        <ConfirmPasswordChangeModal :show="showPasswordModal" :processing="passwordForm.processing"
            @close="closePasswordModal" @confirm="confirmPasswordChange" />

        <DeleteAdminModal :show="showDeleteModal" :processing="deleteForm.processing"
            :password-error="deleteForm.errors.password" @close="closeDeleteModal" @submit="submitDeleteAccount" />
    </AdminLayout>
</template>
