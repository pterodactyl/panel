import i18n, { type i18n as I18n, type Resource } from 'i18next';
import { hashKey, type QueryClient } from '@tanstack/react-query';
import { initReactI18next } from 'react-i18next';
import type { HttpBackendOptions } from 'i18next-http-backend';
import I18NextHttpBackend from 'i18next-http-backend';
import I18NextMultiloadBackendAdapter from 'i18next-multiload-backend-adapter';
import { currentUserQueryKey } from '@/api/account/queries';
import type { UserData } from '@/api/account/types';
import type { SiteSettings } from '@/api/settings/types';
import { getBootstrapExtensions, getBootstrapSiteSettings, getBootstrapUser } from '@/bootstrap';
import { translationOptions } from '@/i18nConfig';

interface MultiloadHttpBackendOptions extends HttpBackendOptions {
    allowMultiLoading: boolean;
}

// Busts the locale cache on every reload under HMR, otherwise once per build.
const hash = import.meta.hot ? Date.now().toString(16) : import.meta.env.WEBPACK_BUILD_HASH;

export const toI18nLanguage = (locale: string): string => locale.replace('_', '-');

export const toPanelLocale = (language: string): string => {
    const [code, region] = language.split('-');

    return region ? `${code}_${region.toUpperCase()}` : code!;
};

/**
 * Extension and language pack translations change when they are upgraded, not with the panel
 * build, so their URL also carries the revision the panel reported for them.
 */
function translationsRevision(namespaces: string[]): string | null | undefined {
    const extensionId = namespaces.length === 1 ? /^ext-(.+)::/.exec(namespaces[0]!)?.[1] : undefined;

    if (extensionId) {
        return getBootstrapExtensions().find((extension) => extension.id === extensionId)?.translations;
    }

    if (namespaces.length > 0 && namespaces.every((namespace) => !namespace.startsWith('ext-'))) {
        return getBootstrapSiteSettings()?.translations;
    }

    return undefined;
}

export function localeLoadPath(namespaces: string[], languages: readonly string[] = []): string {
    const locale = languages.length > 0 ? languages.map(toPanelLocale).join('+') : '{{lng}}';
    const path = `/locales/locale.json?locale=${locale}&namespace={{ns}}`;
    const revision = translationsRevision(namespaces);

    return revision ? `${path}&revision=${revision}` : path;
}

export const parseLocaleResponse = (data: string): Resource =>
    Object.fromEntries(
        Object.entries(JSON.parse(data) as Resource).map(([locale, namespaces]) => [toI18nLanguage(locale), namespaces])
    );

export function resolveLanguage(
    language: string | null | undefined,
    settings: Pick<SiteSettings, 'locale' | 'languages'> | undefined = getBootstrapSiteSettings()
): string {
    const available = !settings?.languages || (!!language && language in settings.languages);

    return toI18nLanguage(language && available ? language : settings?.locale || 'en');
}

export const initialLanguage = (): string => resolveLanguage(getBootstrapUser()?.language);

export function followCurrentUserLanguage(queryClient: QueryClient, instance: I18n = i18n): () => void {
    const currentUserHash = hashKey(currentUserQueryKey);

    return queryClient.getQueryCache().subscribe((event) => {
        if (event.type !== 'added' && event.type !== 'updated') {
            return;
        }

        if (event.query.queryHash !== currentUserHash) {
            return;
        }

        const user = event.query.state.data as UserData | undefined;

        if (!user) {
            return;
        }

        const language = resolveLanguage(user.language);

        if (language !== instance.language) {
            void instance.changeLanguage(language);
        }
    });
}

void i18n
    .use(I18NextMultiloadBackendAdapter)
    .use(initReactI18next)
    .init({
        ...translationOptions,
        debug: import.meta.env.DEV,
        lng: initialLanguage(),
        backend: {
            backend: I18NextHttpBackend,
            backendOption: {
                loadPath: (languages, namespaces) => localeLoadPath(namespaces, languages),
                parse: parseLocaleResponse,
                queryStringParams: { hash },
                allowMultiLoading: true,
            } satisfies MultiloadHttpBackendOptions,
        },
    });

export default i18n;
