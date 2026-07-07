import { useToast } from '@/composables/useToast';
import { usePage, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Login form state + submission for the admin login page.
 * Shows flashed errors and the optional one-time status message as toasts.
 */
export function useAdminLoginSubmission(statusMessage?: string) {
    const { add } = useToast();
    const page = usePage();

    const form = useForm({
        email: '',
        password: '',
    });

    const isLocked = ref(false);
    const isBlocked = ref(false);

    function showFlashError() {
        const error = page.props.flash?.error;
        if (error) add({ type: 'error', message: error });
    }

    /** Call once on mount: shows the status message, then any flashed error. */
    function showInitialMessages() {
        if (statusMessage) add({ type: 'success', message: statusMessage });
        showFlashError();
    }

    function submit() {
        if (isBlocked.value || form.processing) return;

        isLocked.value = false;
        form.post(route('login'), {
            onSuccess: () => showFlashError(),
            onError: (errors) => {
                if (errors.locked) {
                    isLocked.value = true;
                    isBlocked.value = true;
                    add({ type: 'error', message: errors.locked });
                } else if (errors.throttle) {
                    isBlocked.value = true;
                    add({ type: 'error', message: errors.throttle });
                }
            },
            onFinish: () => form.reset('password'),
        });
    }

    return { form, submit, showInitialMessages, isLocked, isBlocked };
}
