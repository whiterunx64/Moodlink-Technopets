import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

export interface PollingOptions {
    /** Poll interval in milliseconds. Default 15s. */
    interval?: number;
    /** Invoke the callback once immediately on mount. Default false. */
    immediate?: boolean;
    /** Pause polling while the browser tab is hidden. Default true. */
    pauseWhenHidden?: boolean;
}

export function usePolling(callback: () => void, options: PollingOptions = {}) {
    const { interval = 15_000, immediate = false, pauseWhenHidden = true } = options;

    let timer: ReturnType<typeof setInterval> | null = null;

    function start() {
        if (timer) return;
        timer = setInterval(callback, interval);
    }

    function stop() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function handleVisibility() {
        if (document.hidden) stop();
        else start();
    }

    onMounted(() => {
        if (immediate) callback();
        start();
        if (pauseWhenHidden) {
            document.addEventListener('visibilitychange', handleVisibility);
        }
    });

    onUnmounted(() => {
        stop();
        if (pauseWhenHidden) {
            document.removeEventListener('visibilitychange', handleVisibility);
        }
    });

    return { start, stop };
}

export function usePollingReload(only: string[], options: PollingOptions = {}) {
    return usePolling(() => router.reload({ only, preserveUrl: true }), options);
}
