import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import { WebpackBackend } from 'i18next-webpack-backend';

// Fixes issue with module not loading due to incorrect typings.
//
// @see https://github.com/i18next/i18next/pull/1442
// @see https://github.com/i18next/i18next/issues/1440#issuecomment-621654219
class FixedWebpackBackend extends WebpackBackend {
    static readonly type: 'backend' = 'backend';
}

i18n.use(initReactI18next).use(FixedWebpackBackend).init({
    lng: 'en-us',
    fallbackLng: 'en-us',
    ns: [ 'en-us', 'de' ],
    debug: process.env.NODE_ENV !== 'production',
    lowerCaseLng: true,
    keySeparator: '.',
    interpolation: {
        escapeValue: false,
    },
    backend: {
        context: require.context('./locales', true, /\.json$/, 'lazy-once'),
    },
});

export default i18n;
