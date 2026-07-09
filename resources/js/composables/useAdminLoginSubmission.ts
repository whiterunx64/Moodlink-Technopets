import { useToast } from '@/composables/useToast';
import { usePage, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

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
    // Seconds remaining on a rate-limit cooldown; 0 when not throttled.
    const cooldown = ref(0);
    let cooldownTimer: ReturnType<typeof setInterval> | undefined;

    /**
     * Disable the sign-in button for `seconds`, counting down and re-enabling
     * it automatically when the rate-limit window elapses. An account lock
     * (isLocked) is deliberately NOT cleared here — that needs an admin.
     */
    function startCooldown(seconds: number) {
        if (cooldownTimer) clearInterval(cooldownTimer);

        cooldown.value = Math.max(1, Math.floor(seconds));
        isBlocked.value = true;

        cooldownTimer = setInterval(() => {
            cooldown.value -= 1;
            if (cooldown.value <= 0) {
                clearInterval(cooldownTimer);
                cooldownTimer = undefined;
                cooldown.value = 0;
                if (!isLocked.value) isBlocked.value = false;
            }
        }, 1000);
    }

    onUnmounted(() => {
        if (cooldownTimer) clearInterval(cooldownTimer);
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
        if (isBlocked.value || form.processing) return;

        isLocked.value = false;
        form.post(route('login'), {
            onSuccess: () => showFlashError(),
            onError: (errors) => {
                if (errors.locked) {
                    // Account lock — permanent until an admin intervenes.
                    isLocked.value = true;
                    isBlocked.value = true;
                    add({ type: 'error', message: errors.locked });
                } else if (errors.throttle) {
                    // Rate limit — re-enable the button after the cooldown.
                    const seconds = Number(errors.retryAfter) || 60;
                    startCooldown(seconds);
                    add({ type: 'error', message: errors.throttle });
                }
            },
            onFinish: () => form.reset('password'),
        });
    }

    return { form, submit, showInitialMessages, isLocked, isBlocked, cooldown };
}
