// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, renderHook, waitFor } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { setCurrentUserQueryData, useCurrentUser } from '@/api/account/queries';
import { siteSettingsQueryOptions, useSiteSettings } from '@/api/settings/queries';
import { updateAdminUserInput, useUpdateAdminUser } from '@/api/admin/users/queries';
import {
    updateAdminAdvancedSettingsInput,
    updateAdminGeneralSettingsInput,
    useUpdateAdminAdvancedSettings,
    useUpdateAdminGeneralSettings,
} from '@/api/admin/settings/queries';

vi.mock('sonner', () => ({ toast: { success: vi.fn() } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);
const bootstrap = window as Window & { PterodactylUser?: object; SiteConfiguration?: object };
const ok = <T,>(data: T) => ({ headers: {}, status: 200, statusText: 'OK', data });

describe('session queries', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
        bootstrap.PterodactylUser = {
            uuid: 'user-uuid',
            username: 'alex',
            email: 'alex@example.test',
            root_admin: true,
            use_totp: false,
            language: 'en',
            created_at: '2026-01-01T00:00:00Z',
            updated_at: '2026-01-01T00:00:00Z',
        };
        bootstrap.SiteConfiguration = { name: 'Panel', locale: 'en', recaptcha: { enabled: false, siteKey: '' } };
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
        delete bootstrap.PterodactylUser;
        delete bootstrap.SiteConfiguration;
    });

    it('refreshes the current user from the account endpoint and keeps fields it does not return', async () => {
        adapter.mockImplementation(async (config) => ({
            config,
            ...ok({
                object: 'user',
                attributes: {
                    id: 1,
                    admin: false,
                    username: 'alexandra',
                    email: 'new@example.test',
                    first_name: 'Alex',
                    last_name: 'Doe',
                    language: 'de',
                },
            }),
        }));
        const { result } = renderHook(() => useCurrentUser(), { wrapper });

        expect(result.current).toMatchObject({ uuid: 'user-uuid', email: 'alex@example.test', useTotp: false });

        act(() => setCurrentUserQueryData(client, { useTotp: true }));
        await act(async () => {
            await client.invalidateQueries({ queryKey: ['account', 'current-user'] });
        });

        await waitFor(() => expect(result.current.email).toBe('new@example.test'));
        expect(new URL(adapter.mock.calls[0]![0].url!, 'https://panel.test').pathname).toBe('/api/client/account');
        expect(result.current).toMatchObject({
            uuid: 'user-uuid',
            username: 'alexandra',
            language: 'de',
            rootAdmin: false,
            useTotp: true,
        });
    });

    it('applies an admin edit of the signed-in user to the current user', async () => {
        adapter.mockImplementation(async (config) => ({
            config,
            ...ok({
                object: 'user',
                attributes: {
                    id: 1,
                    uuid: 'user-uuid',
                    username: 'renamed',
                    email: 'renamed@example.test',
                    language: 'fr',
                    root_admin: true,
                    '2fa': true,
                },
            }),
        }));
        const user = renderHook(() => useCurrentUser(), { wrapper });
        const update = renderHook(() => useUpdateAdminUser(), { wrapper });

        await act(async () => {
            await update.result.current.mutateAsync(
                updateAdminUserInput(1, {
                    email: 'renamed@example.test',
                    username: 'renamed',
                    nameFirst: 'Alex',
                    nameLast: 'Doe',
                    password: '',
                    rootAdmin: true,
                    language: 'fr',
                    extensions: {},
                })
            );
        });

        await waitFor(() =>
            expect(user.result.current).toMatchObject({
                uuid: 'user-uuid',
                username: 'renamed',
                email: 'renamed@example.test',
                language: 'fr',
                useTotp: true,
            })
        );
    });

    it('applies saved general and advanced settings to the site settings without a request', async () => {
        adapter.mockImplementation(async (config) => ({ config, headers: {}, status: 204, statusText: '', data: '' }));
        const settings = renderHook(() => useSiteSettings(), { wrapper });
        const general = renderHook(() => useUpdateAdminGeneralSettings(), { wrapper });
        const advanced = renderHook(() => useUpdateAdminAdvancedSettings(), { wrapper });

        await act(async () => {
            await general.result.current.mutateAsync(
                updateAdminGeneralSettingsInput({ name: 'Renamed Panel', twoFactorRequired: 0, locale: 'de' })
            );
            await advanced.result.current.mutateAsync(
                updateAdminAdvancedSettingsInput({
                    recaptchaEnabled: true,
                    recaptchaSecretKey: 'secret',
                    recaptchaWebsiteKey: 'site-key',
                    guzzleTimeout: 15,
                    guzzleConnectTimeout: 5,
                    allocationsEnabled: false,
                    allocationsRangeStart: '',
                    allocationsRangeEnd: '',
                })
            );
            await client.invalidateQueries({ queryKey: siteSettingsQueryOptions().queryKey });
        });

        await waitFor(() =>
            expect(settings.result.current).toEqual({
                name: 'Renamed Panel',
                locale: 'de',
                recaptcha: { enabled: true, siteKey: 'site-key' },
            })
        );
        expect(adapter.mock.calls.every(([config]) => config.method === 'put')).toBe(true);
    });
});
