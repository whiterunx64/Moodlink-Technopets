<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useToast } from '@/composables/useToast';
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const { add } = useToast();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

onMounted(() => {
    if (props.status) {
        add({
            type: 'success',
            message: props.status,
        });
    }
});

const submit = () => {
    form.post(route('login'), {
        // clear password after request
        onFinish: () => form.reset('password'),
    });
};

</script>

<template>
    <GuestLayout>
        <!-- page title branding header -->
        <h1 class="fade-up fade-up-1 mb-6 text-3xl font-bold text-white sm:text-4xl">
            Moodlink
        </h1>

        <!-- login form container card -->
        <div
            class="border-border-light fade-up fade-up-2 bg-ml-card isolate w-full max-w-sm rounded-2xl border px-8 py-8 shadow-sm sm:max-w-md lg:max-w-lg">
            <!-- authentication form submission wrapper -->
            <form @submit.prevent="submit">
                <div class="space-y-4">

                    <!-- email input field section -->
                    <div>
                        <InputLabel value="Email" for="email" class="fade-up fade-up-3" />

                        <TextInput id="email" v-model="form.email" type="email" name="email" class="fade-up fade-up-3"
                            :error="!!form.errors.email" autocomplete="email" />

                        <!-- inline email validation error -->
                        <p v-if="form.errors.email" class="mt-1 text-sm text-login-error-text">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- password input field section -->
                    <div>
                        <InputLabel value="Password" for="password" class="fade-up fade-up-4" />

                        <TextInput id="password" v-model="form.password" type="password" name="password"
                            class="fade-up fade-up-4" :error="!!form.errors.password" autocomplete="current-password" />

                        <!-- inline password validation error -->
                        <p v-if="form.errors.password" class="mt-1 text-sm text-login-error-text">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- remember me checkbox section -->
                    <div class="fade-up fade-up-4 flex items-center gap-2">
                        <Checkbox id="remember" v-model:checked="form.remember" />

                        <InputLabel for="remember" value="Remember me" class="cursor-pointer" />
                    </div>

                    <!-- submit button loading state -->
                    <PrimaryButton type="submit" :disabled="form.processing" class="fade-up fade-up-5 mt-2 w-full">
                        {{ form.processing ? 'Signing in…' : 'Sign In' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>