import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LocalStorageBackend from 'i18next-localstorage-backend';
import { WebpackBackend } from 'i18next-webpack-backend';
import Backend from 'i18next-chained-backend';

let cacheExpirationTime = 7 * 24 * 60 * 60 * 1000; // 7 days, in milliseconds

// Set ExpirationTime to 0, so you can see changes directly
if (process.env.NODE_ENV !== 'production') {
    cacheExpirationTime = 0;
}

i18n
    .use(Backend)
    .use(initReactI18next)
    .init({
        debug: process.env.NODE_ENV !== 'production',
        lng: 'de',
        fallbackLng: 'en',
        keySeparator: '.',
        interpolation: {
            // not needed for react as it escapes by default
            escapeValue: false,
        },
        backend: {
            backends: [
                LocalStorageBackend,
                WebpackBackend,
            ],
            backendOptions: [ {
                prefix: 'pterodactyl_lng__',
                expirationTime: cacheExpirationTime,
                store: window.localStorage,
                defaultVersion: 'v1', // TODO: Get version from config/app.php
            }, {
                context: require.context('../locales', true, /\.json$/, 'lazy'),
            } ],
        },
    });

// i18n.loadNamespaces(['validation']);

export default i18n;
