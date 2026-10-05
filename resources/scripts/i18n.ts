import i18n, { type i18n as I18n } from 'i18next';
import { hashKey, type QueryClient } from '@tanstack/react-query';
import { initReactI18next } from 'react-i18next';
import type { HttpBackendOptions } from 'i18next-http-backend';
import I18NextHttpBackend from 'i18next-http-backend';
import I18NextMultiloadBackendAdapter from 'i18next-multiload-backend-adapter';
import { currentUserQueryKey } from '@/api/account/queries';
import type { UserData } from '@/api/account/types';
import { getBootstrapSiteSettings, getBootstrapUser } from '@/bootstrap';

interface MultiloadHttpBackendOptions extends HttpBackendOptions {
    allowMultiLoading: boolean;
}

// Busts the locale cache on every reload under HMR, otherwise once per build.
const hash = import.meta.hot ? Date.now().toString(16) : import.meta.env.WEBPACK_BUILD_HASH;

export const initialLanguage = (): string => getBootstrapUser()?.language || getBootstrapSiteSettings()?.locale || 'en';

export function followCurrentUserLanguage(queryClient: QueryClient, instance: I18n = i18n): () => void {
    const currentUserHash = hashKey(currentUserQueryKey);

    return queryClient.getQueryCache().subscribe((event) => {
        if (event.type !== 'added' && event.type !== 'updated') return;
        if (event.query.queryHash !== currentUserHash) return;

        const language = (event.query.state.data as UserData | undefined)?.language;
        if (language && language !== instance.language) {
            void instance.changeLanguage(language);
        }
    });
}

i18n.use(I18NextMultiloadBackendAdapter)
    .use(initReactI18next)
    .init({
        debug: import.meta.env.DEV,
        lng: initialLanguage(),
        fallbackLng: 'en',
        keySeparator: '.',
        backend: {
            backend: I18NextHttpBackend,
            backendOption: {
                loadPath: '/locales/locale.json?locale={{lng}}&namespace={{ns}}',
                queryStringParams: { hash },
                allowMultiLoading: true,
            } satisfies MultiloadHttpBackendOptions,
        },
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;
