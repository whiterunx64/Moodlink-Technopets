<script setup lang="ts">
import ChangePasswordForm from '@/Components/Profile/ChangePasswordForm.vue';
import DangerZone from '@/Components/Profile/DangerZone.vue';
import NotificationPrefs from '@/Components/Profile/NotificationPrefs.vue';
import ProfileCard from '@/Components/Profile/ProfileCard.vue';
import ProfileInfoForm from '@/Components/Profile/ProfileInfoForm.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmPasswordChangeModal from '@/Pages/Profile/Modal/ConfirmPasswordChangeModal.vue';
import ConfirmProfileUpdateModal from '@/Pages/Profile/Modal/ConfirmProfileUpdateModal.vue';
import DeleteAdminModal from '@/Pages/Profile/Modal/DeleteAdminModal.vue';
import { useToast } from '@/composables/useToast';
import type {
    AdminProfile,
    NotificationPreferences,
    PasswordForm,
    ProfileSettingsPageProps,
} from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<ProfileSettingsPageProps>();

const { add } = useToast();

const NOTIFICATION_DEFAULTS: NotificationPreferences = {
    new_flags: true,
    appointments: true,
    escalations: true,
    weekly_reports: false,
    system_updates: false,
};

const profile = ref<AdminProfile>({
    first_name: props.admin?.first_name ?? '',
    last_name: props.admin?.last_name ?? '',
    phone: props.admin?.phone ?? '',
    role: props.admin?.role ?? '',
    department: '',
});

const notifications = ref<NotificationPreferences>({
    ...NOTIFICATION_DEFAULTS,
    ...(props.notifications ?? {}),
});

// Persist notification toggles to Supabase user_metadata whenever they change.
const notificationForm = useForm({ notifications: { ...notifications.value } });

watch(
    notifications,
    (value) => {
        notificationForm.notifications = { ...value };
        notificationForm.patch(route('profile.preferences.update'), {
            preserveScroll: true,
            onSuccess: () =>
                add({
                    type: 'success',
                    message: 'Notification preferences saved.',
                }),
            onError: () =>
                add({
                    type: 'error',
                    message: 'Could not save preferences. Please try again.',
                }),
        });
    },
    { deep: true },
);

// --- Avatar upload ---
const avatarForm = useForm<{ avatar: File | null }>({ avatar: null });

const AVATAR_MAX_BYTES = 2 * 1024 * 1024; // 2 MB — matches server validation
const AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

function onChangeAvatar(file: File) {

    if (!AVATAR_TYPES.includes(file.type)) {
        add({ type: 'error', message: 'Please choose a JPG, PNG, or WebP image.' });
        return;
    }
    if (file.size > AVATAR_MAX_BYTES) {
        add({
            type: 'error',
            message: 'That image is too large. Please choose one under 2 MB.',
        });
        return;
    }

    avatarForm.avatar = file;
    avatarForm.post(route('profile.avatar.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () =>
            add({ type: 'success', message: 'Avatar updated successfully.' }),
        onError: (errors) =>
            add({
                type: 'error',
                message:
                    errors.avatar ??
                    'Could not upload avatar. Please try again.',
            }),
        onFinish: () => avatarForm.reset(),
    });
}

const showProfileModal = ref(false);
const profileForm = useForm({
    first_name: profile.value.first_name,
    last_name: profile.value.last_name,
    phone: profile.value.phone,
});

// Stash the entered values and ask the user to confirm before submitting.
function onSaveProfile(updated: AdminProfile) {
    profile.value = updated;

    profileForm.first_name = updated.first_name;
    profileForm.last_name = updated.last_name;
    profileForm.phone = updated.phone;

    showProfileModal.value = true;
}

function closeProfileModal() {
    showProfileModal.value = false;
}

function confirmProfileUpdate() {
    profileForm.patch(route('profile.metadata.update'), {
        preserveScroll: true,
        onSuccess: () =>
            add({ type: 'success', message: 'Profile updated successfully.' }),
        onError: (errors) => {
            const message =
                errors.first_name ??
                errors.last_name ??
                errors.phone ??
                errors.profile ??
                'Unable to update profile. Please try again.';
            add({ type: 'error', message });
        },
        onFinish: () => closeProfileModal(),
    });
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
const passwordErrors = computed<Partial<Record<keyof PasswordForm, string>>>(
    () => ({
        current: passwordForm.errors.current_password,
        new_pass: passwordForm.errors.password,
    }),
);

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
    passwordForm.password = form.new_pass;
    passwordForm.password_confirmation = form.confirm;

    passwordForm.put(route('profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            changePasswordForm.value?.reset();
            add({ type: 'success', message: 'Password updated successfully.' });
        },
        onError: (errors) => {
            const message =
                errors.current_password ??
                errors.password ??
                'Unable to update password. Please try again.';
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
    deleteForm.delete(route('profile.account.destroy'), {
        preserveScroll: true,
        onError: () => {},
        onFinish: () => deleteForm.reset(),
    });
}
</script>

<template>
    <AdminLayout title="Account & Settings">
        <div class="max-w-4xl space-y-6">
            <ProfileCard
                :first-name="profile.first_name"
                :last-name="profile.last_name"
                :role="profile.role"
                :status="admin?.status ?? 'inactive'"
                :avatar-url="admin?.avatar ?? null"
                :uploading="avatarForm.processing"
                @change-avatar="onChangeAvatar"
            />

            <ProfileInfoForm
                :profile="profile"
                :processing="profileForm.processing"
                :errors="profileForm.errors"
                @save="onSaveProfile"
            />

            <ChangePasswordForm
                ref="changePasswordForm"
                :processing="passwordForm.processing"
                :errors="passwordErrors"
                @submit="onChangePassword"
            />

            <NotificationPrefs v-model="notifications" />

            <DangerZone @logout="onLogout" @delete-account="onDeleteAccount" />
        </div>

        <ConfirmProfileUpdateModal
            :show="showProfileModal"
            :processing="profileForm.processing"
            @close="closeProfileModal"
            @confirm="confirmProfileUpdate"
        />

        <ConfirmPasswordChangeModal
            :show="showPasswordModal"
            :processing="passwordForm.processing"
            @close="closePasswordModal"
            @confirm="confirmPasswordChange"
        />

        <DeleteAdminModal
            :show="showDeleteModal"
            :processing="deleteForm.processing"
            :password-error="deleteForm.errors.password"
            @close="closeDeleteModal"
            @submit="submitDeleteAccount"
        />
    </AdminLayout>
</template>
