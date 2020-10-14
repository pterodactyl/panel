import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LocalStorageBackend from 'i18next-localstorage-backend';
import XHR from 'i18next-xhr-backend';
import Backend from 'i18next-chained-backend';

i18n
    .use(Backend)
    .use(initReactI18next)
    .init({
        debug: process.env.NODE_ENV !== 'production',
        lng: 'en',
        fallbackLng: 'en',
        keySeparator: '.',
        backend: {
            backends: [
                LocalStorageBackend,
                XHR,
            ],
            backendOptions: [ {
                prefix: 'pterodactyl_lng__',
                expirationTime: 7 * 24 * 60 * 60 * 1000, // 7 days, in milliseconds
                store: window.localStorage,
            }, {
                loadPath: '/locales/{{lng}}/{{ns}}.json',
            } ],
        },
    });

// i18n.loadNamespaces(['validation']);

export default i18n;
