/** @vitest-environment jsdom */
import { QueryClient } from '@tanstack/react-query';
import { createInstance } from 'i18next';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { UserData } from '@/api/account/types';

vi.mock('i18next-multiload-backend-adapter', () => ({
    default: { type: 'backend', init() {}, read: (_language: string, _namespace: string, done: () => void) => done() },
}));

type BootstrapWindow = Window & { PterodactylUser?: unknown; SiteConfiguration?: unknown };

const user = (language: string) => ({
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

    client.setQueryData<Pick<UserData, 'language'>>(['unrelated'], { language: 'fi' });
    expect(instance.language).toBe('nl');
    expect(client.getQueryData<UserData>(currentUserQueryKey)?.language).toBe('nl');

    stop();
    setCurrentUserQueryData(client, { language: 'es' });
    expect(instance.language).toBe('nl');
    client.clear();
});
