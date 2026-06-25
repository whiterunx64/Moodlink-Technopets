import { onBeforeUnmount, ref, watch } from 'vue';

export function useDismissibleError(autoDismissMs = 10000) {
    const errorMessage = ref<string | null>(null);
    const errorPopup = ref<HTMLElement | null>(null);
    let autoDismissTimer: ReturnType<typeof setTimeout> | null = null;

    function clearAutoDismissTimer() {
        if (autoDismissTimer) {
            clearTimeout(autoDismissTimer);
            autoDismissTimer = null;
        }
    }

    function dismissError() {
        clearAutoDismissTimer();
        errorMessage.value = null;
    }

    function showError(message: string) {
        errorMessage.value = message;
        clearAutoDismissTimer();
        autoDismissTimer = setTimeout(dismissError, autoDismissMs);
    }

    function dismissOnOutsideClick(event: MouseEvent) {
        const popupEl = errorPopup.value;
        if (popupEl && !popupEl.contains(event.target as Node)) {
            dismissError();
        }
    }

    // Listen for outside clicks only while a message is showing.
    watch(errorMessage, (message) => {
        if (message) {
            document.addEventListener('mousedown', dismissOnOutsideClick);
        } else {
            document.removeEventListener('mousedown', dismissOnOutsideClick);
        }
    });

    onBeforeUnmount(() => {
        clearAutoDismissTimer();
        document.removeEventListener('mousedown', dismissOnOutsideClick);
    });

    return { errorMessage, errorPopup, showError, dismissError };
}
