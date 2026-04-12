import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import { __, transChoice } from './utils/lang';

// Clear corrupted history state that may contain non-clonable Echo references
// This prevents DataCloneError when Inertia tries to restoreState on page reload
try {
    const state = window.history.state;
    if (state && typeof state === 'object') {
        const cleaned = {};
        for (const key of Object.keys(state)) {
            try {
                structuredClone(state[key]);
                cleaned[key] = state[key];
            } catch {
                // Skip non-clonable values (e.g., Echo references)
            }
        }
        window.history.replaceState(cleaned, '');
    }
} catch {
    // Ignore errors during cleanup
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const toastOptions = {
    position: 'top-right',
    timeout: 2000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, toastOptions);

        // Make translation helpers globally available in Vue templates
        app.config.globalProperties.__ = __;
        app.config.globalProperties.transChoice = transChoice;

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
