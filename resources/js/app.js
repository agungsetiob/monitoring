import '../css/app.css';
import './bootstrap';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { faPrescriptionBottleMedical, faBedPulse, faMars, faVenus, 
    faFaceSadCry, faPills, faChartBar,
    faChevronLeft, faChevronRight

 } from '@fortawesome/free-solid-svg-icons';

library.add(faPrescriptionBottleMedical, faBedPulse, faMars, faVenus, 
    faFaceSadCry, faPills, faChartBar,
    faChevronLeft, faChevronRight);


dayjs.extend(relativeTime);

const appName = import.meta.env.VITE_APP_NAME || 'Monitoring';

createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.config.globalProperties.$dayjs = dayjs;

        app.use(plugin)
           .use(ZiggyVue)
           .component('font-awesome-icon', FontAwesomeIcon)
           .mount(el);

        return app;
    },
    progress: false,
});
