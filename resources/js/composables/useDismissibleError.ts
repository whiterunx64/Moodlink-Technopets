import { onBeforeUnmount, ref, watch } from 'vue';

export function useDismissibleError(autoDismissMs = 10000) {
    const errorMessage = ref<string | null>(null);
    const errorPopup = ref<HTMLElement | null>(null);
    let timer: ReturnType<typeof setTimeout> | null = null;

    function clearTimer() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    function dismissError() {
        clearTimer();
        errorMessage.value = null;
    }

    function showError(message: string) {
        errorMessage.value = message;
        clearTimer();
        timer = setTimeout(dismissError, autoDismissMs);
    }

    function onPointerDown(event: MouseEvent) {
        const el = errorPopup.value;
        if (el && !el.contains(event.target as Node)) {
            dismissError();
        }
    }

    // Listen for outside clicks only while a message is showing.
    watch(errorMessage, (message) => {
        if (message) {
            document.addEventListener('mousedown', onPointerDown);
        } else {
            document.removeEventListener('mousedown', onPointerDown);
        }
    });

    onBeforeUnmount(() => {
        clearTimer();
        document.removeEventListener('mousedown', onPointerDown);
    });

    return { errorMessage, errorPopup, showError, dismissError };
}
