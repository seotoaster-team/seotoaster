import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import App from './components/app/index.vue';
import {store} from './config/store/';
import "regenerator-runtime/runtime";

import routes from './config/routing/routes';
import messages from './localization';
import VueMultiselect from 'vue-multiselect';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'

const userWebApp =  createApp(App);

const router = createRouter({
    history: createWebHistory(window.location.pathname),
    routes
})

console.log(window.location.pathname);

const i18n = createI18n({
    locale: 'en', // set locale
    messages,
})

userWebApp.use(router);
userWebApp.use(i18n);
userWebApp.component('VueMultiselect', VueMultiselect);
userWebApp.component('VueDatePicker', VueDatePicker);
userWebApp.use(store);
userWebApp.use(Autocomplete);


userWebApp.mount('#leads-screen-config-block');

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        console.log(to);
        store.commit('checkToken');
        if (store.getters.isLoggedIn) {
            console.log(store.getters.isLoggedIn);
            next();
            return
        } else {
            next({path: '/login'})
        }
    } else {
        let skip = false;

        if (to.hash !== '' && from.hash === '') {
            let pathParams = decodeURI(to.hash);
            let leadRouteInfo = pathParams.match('(#lead\\/\\d*)');

            if (!leadRouteInfo) {
                next({ name: 'grid'});
            }

            let leadId = leadRouteInfo[0].replace('#lead/', '');
            let additionalParams= [];

            pathParams = pathParams.replace(new RegExp('#lead\/\\d*'), '');

            if (pathParams !== '' && pathParams.indexOf('?') > -1) {
                additionalParams = getParams(pathParams.replace('?', ''));
            }

            if (to.hash === from.path.replace('/', '')) {
                skip = true;
                next({ name: 'grid'});
            } else {
                skip = true;
                next({ name: 'lead', params: {'id': leadId}, query:additionalParams});
            }
        }

        if (skip === false) {
            next();
        }
    }
});

function getParams(pathParams) {
    let result = {},
        tmpData = [];

    pathParams
        .split("&")
        .forEach(function (item) {
            tmpData = item.split("=");
            if (tmpData[0] !== '') {
                result[decodeURIComponent(tmpData[0])] = decodeURIComponent(tmpData[1]);
            }
        });
    return result;
}