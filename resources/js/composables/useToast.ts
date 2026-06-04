import { toast } from 'vue-sonner';

export interface ToastOptions {
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
}

export function useToast() {
    function add({ type, message }: ToastOptions) {
        toast[type](message);
    }

    return { add };
}