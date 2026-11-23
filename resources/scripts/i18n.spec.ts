/** @vitest-environment jsdom */
import { QueryClient } from '@tanstack/react-query';
import { createInstance } from 'i18next';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { UserData } from '@/api/account/types';

vi.mock('i18next-multiload-backend-adapter', () => ({
    default: { type: 'backend', init() {}, read: (_language: string, _namespace: string, done: () => void) => done() },
}));

type BootstrapWindow = Window & { PterodactylUser?: unknown; SiteConfiguration?: unknown };

const user = (language: string | null) => ({
    uuid: 'user',
    username: 'alex',
    email: 'alex@example.com',
    root_admin: false,
    use_totp: false,
    language,
    updated_at: '2026-01-01T00:00:00Z',
    created_at: '2026-01-01T00:00:00Z',
});

beforeEach(() => {
    vi.resetModules();
});
afterEach(() => {
    delete (window as BootstrapWindow).PterodactylUser;
    delete (window as BootstrapWindow).SiteConfiguration;
});

it('starts in the signed-in user language, then the site locale, then English', async () => {
    const { initialLanguage } = await import('@/i18n');

    expect(initialLanguage()).toBe('en');

    (window as BootstrapWindow).SiteConfiguration = { locale: 'fr' };
    expect(initialLanguage()).toBe('fr');

    (window as BootstrapWindow).PterodactylUser = user('de');
    expect(initialLanguage()).toBe('de');

    (window as BootstrapWindow).PterodactylUser = user(null);
    expect(initialLanguage()).toBe('fr');
});

it('uses the language a user chose while it is available, else the panel default', async () => {
    const { resolveLanguage } = await import('@/i18n');
    const settings = { locale: 'de', languages: { de: 'Deutsch', en: 'English', pt_BR: 'Português (Brasil)' } };

    expect(resolveLanguage(null, settings)).toBe('de');
    expect(resolveLanguage('en', settings)).toBe('en');
    expect(resolveLanguage('pt_BR', settings)).toBe('pt-BR');
    expect(resolveLanguage('fr', settings)).toBe('de');
    expect(resolveLanguage(null, undefined)).toBe('en');
});

it('converts between i18next languages and panel locales', async () => {
    const { toI18nLanguage, toPanelLocale } = await import('@/i18n');

    expect(toI18nLanguage('pt_BR')).toBe('pt-BR');
    expect(toI18nLanguage('de')).toBe('de');
    expect(toPanelLocale('pt-BR')).toBe('pt_BR');
    expect(toPanelLocale('pt-br')).toBe('pt_BR');
    expect(toPanelLocale('de')).toBe('de');
    expect(toI18nLanguage('zh_Hant_TW')).toBe('zh-Hant-TW');
    expect(toPanelLocale('zh-hant-tw')).toBe('zh_Hant_TW');
    expect(toPanelLocale('sr-Latn')).toBe('sr_Latn');
    expect(toPanelLocale('es-419')).toBe('es_419');
    expect(toPanelLocale('fil')).toBe('fil');
});

it('follows language changes of the cached current user', async () => {
    (window as BootstrapWindow).PterodactylUser = user('en');
    const { followCurrentUserLanguage } = await import('@/i18n');
    const { currentUserQueryKey, currentUserQueryOptions, setCurrentUserQueryData } =
        await import('@/api/account/queries');
    const instance = createInstance();

    await instance.init({ lng: 'en', resources: {} });
    const client = new QueryClient();
    const stop = followCurrentUserLanguage(client, instance);

    await client.ensureQueryData(currentUserQueryOptions());
    expect(instance.language).toBe('en');

    setCurrentUserQueryData(client, { language: 'nl' });
    await vi.waitFor(() => expect(instance.language).toBe('nl'));

    setCurrentUserQueryData(client, { language: 'pt_BR' });
    await vi.waitFor(() => expect(instance.language).toBe('pt-BR'));

    client.setQueryData<Pick<UserData, 'language'>>(['unrelated'], { language: 'fi' });
    expect(instance.language).toBe('pt-BR');
    expect(client.getQueryData<UserData>(currentUserQueryKey)?.language).toBe('pt_BR');

    stop();
    setCurrentUserQueryData(client, { language: 'es' });
    expect(instance.language).toBe('pt-BR');
    client.clear();
});

it('refetches cached server text when the language changes', async () => {
    (window as BootstrapWindow).PterodactylUser = user('en');
    const { followCurrentUserLanguage } = await import('@/i18n');
    const { currentUserQueryKey, currentUserQueryOptions, setCurrentUserQueryData } =
        await import('@/api/account/queries');
    const instance = createInstance();

    await instance.init({ lng: 'en', resources: {} });
    const client = new QueryClient();
    const stop = followCurrentUserLanguage(client, instance);

    await client.ensureQueryData(currentUserQueryOptions());
    client.setQueryData(['permissions'], { description: 'Start the server' });
    expect(client.getQueryState(['permissions'])?.isInvalidated).toBe(false);

    setCurrentUserQueryData(client, { language: 'de' });
    await vi.waitFor(() => expect(client.getQueryState(['permissions'])?.isInvalidated).toBe(true));
    expect(client.getQueryState(currentUserQueryKey)?.isInvalidated).toBe(false);

    stop();
    client.clear();
});

it('re-renders once the date locale of a new language has loaded', async () => {
    const { DATE_LOCALE_LOADED, followDateLocale } = await import('@/i18n');
    const { default: dayjs } = await import('@/lib/dayjs');
    const instance = createInstance();

    await instance.init({ lng: 'en', resources: {} });
    const stop = followDateLocale(instance);
    const loaded = vi.fn();

    instance.on(DATE_LOCALE_LOADED, loaded);

    await instance.changeLanguage('de');
    await vi.waitFor(() => expect(loaded).toHaveBeenCalledWith('de'));
    expect(dayjs.locale()).toBe('de');

    stop();
    await instance.changeLanguage('fr');
    expect(loaded).toHaveBeenCalledTimes(1);
});

it('adds the revision of an extension to the URL of its translations only', async () => {
    (window as BootstrapWindow).SiteConfiguration = {
        extensions: [
            { id: 'notes', entry: '/notes.js', translations: 'a1b2' },
            { id: 'plain', entry: '/plain.js', translations: null },
        ],
    };
    const { localeLoadPath } = await import('@/i18n');
    const core = '/locales/locale.json?locale={{lng}}&namespace={{ns}}';

    expect(localeLoadPath(['ext-notes::messages'])).toBe(`${core}&revision=a1b2`);
    expect(localeLoadPath(['strings'])).toBe(core);
    expect(localeLoadPath(['ext-plain::messages'])).toBe(core);
    expect(localeLoadPath(['ext-missing::messages'])).toBe(core);
    // One request for several namespaces has no single revision to carry.
    expect(localeLoadPath(['ext-notes::messages', 'strings'])).toBe(core);
});

it('adds the revision of the enabled language packs to the URL of core translations', async () => {
    (window as BootstrapWindow).SiteConfiguration = {
        translations: 'c3d4',
        extensions: [{ id: 'notes', entry: '/notes.js', translations: 'a1b2' }],
    };
    const { localeLoadPath } = await import('@/i18n');

    expect(localeLoadPath(['auth'])).toBe('/locales/locale.json?locale={{lng}}&namespace={{ns}}&revision=c3d4');
    expect(localeLoadPath(['translation'])).toBe('/locales/locale.json?locale={{lng}}&namespace={{ns}}&revision=c3d4');
    expect(localeLoadPath(['auth', 'activity'])).toBe(
        '/locales/locale.json?locale={{lng}}&namespace={{ns}}&revision=c3d4'
    );
    expect(localeLoadPath(['ext-notes::messages'])).toBe(
        '/locales/locale.json?locale={{lng}}&namespace={{ns}}&revision=a1b2'
    );
    expect(localeLoadPath(['ext-notes::messages', 'auth'])).toBe(
        '/locales/locale.json?locale={{lng}}&namespace={{ns}}'
    );
});

it('requests panel locales for the languages i18next loads', async () => {
    const { localeLoadPath } = await import('@/i18n');

    expect(localeLoadPath(['auth'], ['pt-BR'])).toBe('/locales/locale.json?locale=pt_BR&namespace={{ns}}');
    expect(localeLoadPath(['auth'], ['de', 'en'])).toBe('/locales/locale.json?locale=de+en&namespace={{ns}}');
});

it('files translations under the i18next language they were requested for', async () => {
    const { parseLocaleResponse } = await import('@/i18n');

    expect(
        parseLocaleResponse(JSON.stringify({ en_AU: { translation: { Servers: 'Ships' } }, en: { translation: {} } }))
    ).toEqual({ 'en-AU': { translation: { Servers: 'Ships' } }, en: { translation: {} } });
});
