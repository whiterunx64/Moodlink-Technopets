import { useToast } from '@/composables/useToast';
import { usePage, useForm } from '@inertiajs/vue3';

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
        form.post(route('login'), {
            onSuccess: () => showFlashError(),
            onFinish: () => form.reset('password'),
        });
    }

    return { form, submit, showInitialMessages };
}
