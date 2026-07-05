import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { Toaster, toast } from 'vue-sonner';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = 'MoodLink';

router.on('invalid', (event) => {
    const status = event.detail.response?.status;
    if (!status || status < 400) {
        return; // let Inertia handle non-error invalid responses
    }

    event.preventDefault(); // suppress the iframe error modal

    // Expired CSRF/session token: reload in place for a fresh token.
    if (status === 419) {
        toast.info('Your session refreshed — please try that again.');
        window.location.reload();
        return;
    }

    window.location.assign(window.location.href);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () =>
                h('div', [
                    h(App, props),
                    h(Toaster, {
                        position: 'top-right',
                        richColors: false,
                        closeButton: true,
                        expand: true,        // full card stack, no overlap
                        visibleToasts: 4,    // how many stay in the visible stack
                        gap: 12,
                        offset: 16,
                    }),
                ]),
        });

        app.use(plugin);
        app.use(ZiggyVue);
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
