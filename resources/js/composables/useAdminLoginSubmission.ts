import { useToast } from '@/composables/useToast';
import { usePage, useForm } from '@inertiajs/vue3';

export function useAdminLoginSubmission(initialStatusMessage?: string) {
    const { add } = useToast();
    const page = usePage();

    const credentials = useForm({
        email: '',
        password: '',
    });

    const surfaceFlashedError = () => {
        const flashedError = page.props.flash?.error;
        if (flashedError) add({ type: 'error', message: flashedError });
    };

    const announceInitialFeedback = () => {
        if (initialStatusMessage) add({ type: 'success', message: initialStatusMessage });
        surfaceFlashedError();
    };

    const submitCredentials = () => {
        credentials.post(route('login'), {
            onFinish: () => {
                credentials.reset('password');
                surfaceFlashedError();
            },
        });
    };

    return {
        credentials,
        submitCredentials,
        announceInitialFeedback,
    };
}
