import i18n, { type i18n as I18n } from 'i18next';
import { hashKey, type QueryClient } from '@tanstack/react-query';
import { initReactI18next } from 'react-i18next';
import type { HttpBackendOptions } from 'i18next-http-backend';
import I18NextHttpBackend from 'i18next-http-backend';
import I18NextMultiloadBackendAdapter from 'i18next-multiload-backend-adapter';
import { currentUserQueryKey } from '@/api/account/queries';
import type { UserData } from '@/api/account/types';
import { getBootstrapExtensions, getBootstrapSiteSettings, getBootstrapUser } from '@/bootstrap';

interface MultiloadHttpBackendOptions extends HttpBackendOptions {
    allowMultiLoading: boolean;
}

// Busts the locale cache on every reload under HMR, otherwise once per build.
const hash = import.meta.hot ? Date.now().toString(16) : import.meta.env.WEBPACK_BUILD_HASH;

const localePath = '/locales/locale.json?locale={{lng}}&namespace={{ns}}';

/**
 * Extension translations change when the extension is upgraded, not with the panel build,
 * so their URL also carries the revision the panel reported for that extension.
 */
export function localeLoadPath(namespaces: string[]): string {
    const extensionId = namespaces.length === 1 ? /^ext-(.+)::/.exec(namespaces[0]!)?.[1] : undefined;
    const revision = extensionId
        ? getBootstrapExtensions().find((extension) => extension.id === extensionId)?.translations
        : undefined;

    return revision ? `${localePath}&revision=${revision}` : localePath;
}

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
                loadPath: (_languages, namespaces) => localeLoadPath(namespaces),
                queryStringParams: { hash },
                allowMultiLoading: true,
            } satisfies MultiloadHttpBackendOptions,
        },
        interpolation: {
            escapeValue: false,
        },
    });

export default i18n;
