import { toast } from 'vue-sonner';

export interface ToastOptions {
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
}

const TITLES: Record<ToastOptions['type'], string> = {
    success: 'Success',
    error: 'Error',
    info: 'Info',
    warning: 'Warning',
};

const DURATIONS: Record<ToastOptions['type'], number> = {
    success: 3500,
    info: 4500,
    warning: 6000,
    error: 8000,
};

const counts = new Map<string, number>();

export function useToast() {
    function add({ type, message }: ToastOptions) {
        const id = `${type}:${message}`;

        const count = (counts.get(id) ?? 0) + 1;
        counts.set(id, count);

        const title =
            count > 1 ? `${TITLES[type]} ×${count}` : TITLES[type];

        const reset = () => counts.delete(id);
        const duration = DURATIONS[type];

        toast[type](title, {
            id,
            description: message,
            duration,
            style: { '--toast-duration': `${duration}ms` } as Record<
                string,
                string
            >,
            onAutoClose: reset,
            onDismiss: reset,
        });
    }

    return { add };
}