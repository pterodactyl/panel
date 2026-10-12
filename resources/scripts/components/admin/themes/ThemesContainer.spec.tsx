// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    AdminTheme,
    AdminThemeSetting,
    AdminThemeSettingsResponse,
    AdminThemesResponse,
} from '@/api/admin/themes/queries';
import http from '@/api/http';
import ThemesContainer from '@/components/admin/themes/ThemesContainer';

const mocks = vi.hoisted(() => ({ notifyError: vi.fn(), toastSuccess: vi.fn() }));

vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));

const theme = (
    attributes: Pick<AdminTheme, 'id' | 'name'> & Partial<AdminTheme>
): AdminThemesResponse['data'][number] => ({
    object: 'theme',
    attributes: {
        version: '1.0.0',
        description: null,
        author: null,
        icon: null,
        icon_url: null,
        builtin: false,
        enabled: true,
        active: false,
        previewing: false,
        extends: 'classic',
        schemes: ['dark', 'light'],
        has_stylesheet: true,
        has_entry: true,
        settings_count: 2,
        problem: null,
        ...attributes,
    },
});

const themes: AdminThemesResponse = {
    object: 'list',
    data: [
        theme({
            id: 'classic',
            name: 'Classic',
            version: null,
            builtin: true,
            active: true,
            extends: null,
            settings_count: 0,
        }),
        theme({ id: 'aurora', name: 'Aurora' }),
        theme({ id: 'broken', name: 'Broken', problem: 'Theme "broken" is not enabled or failed to load.' }),
    ],
    meta: { active: 'classic', preview: null, safe_mode: false },
};

const setting = (
    attributes: Pick<AdminThemeSetting, 'key' | 'type' | 'label' | 'default' | 'value'> & Partial<AdminThemeSetting>
): AdminThemeSettingsResponse['data'][number] => ({
    object: 'theme_setting',
    attributes: {
        help: null,
        token: null,
        attribute: null,
        options: [],
        min: null,
        max: null,
        step: null,
        unit: null,
        max_length: null,
        accept: [],
        max_kilobytes: null,
        ...attributes,
    },
});

const settings: AdminThemeSettingsResponse = {
    object: 'list',
    data: [
        setting({
            key: 'accent',
            type: 'color',
            label: 'Accent',
            default: '#8b5cf6',
            value: '#8b5cf6',
            token: '--accent',
        }),
        setting({
            key: 'compact',
            type: 'toggle',
            label: 'Compact spacing',
            default: false,
            value: false,
            attribute: 'data-aurora-compact',
        }),
    ],
};

const originalAdapter = http.defaults.adapter;
const requests = vi.fn<(config: InternalAxiosRequestConfig) => unknown>();

const respond = (
    config: InternalAxiosRequestConfig,
    data: AdminThemesResponse | AdminThemesResponse['data'][number] | AdminThemeSettingsResponse
): AxiosResponse => ({
    data,
    status: 200,
    statusText: 'OK',
    headers: {},
    config,
});

const setup = () => {
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false, staleTime: Infinity }, mutations: { retry: false } },
    });

    render(
        <QueryClientProvider client={queryClient}>
            <ThemesContainer />
        </QueryClientProvider>
    );

    return userEvent.setup();
};

describe('themes page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        http.defaults.adapter = async (config) => {
            requests(config);

            if (config.url?.endsWith('/settings')) {
                return respond(config, settings);
            }

            return respond(config, config.method === 'get' ? themes : themes.data[1]);
        };
    });

    afterEach(() => {
        cleanup();
        http.defaults.adapter = originalAdapter;
        document.documentElement.removeAttribute('style');
        document.documentElement.removeAttribute('data-theme');
        document.documentElement.removeAttribute('data-aurora-compact');
    });

    it('lists the built-in and installed themes and activates one', async () => {
        const user = setup();
        const card = (await screen.findByRole('heading', { name: 'Aurora' })).closest('article')!;

        expect(screen.getByRole('heading', { name: 'Classic' }).closest('article')).toHaveTextContent('Active');
        expect(screen.getByText('Theme "broken" is not enabled or failed to load.')).toBeInTheDocument();
        expect(
            within(screen.getByRole('heading', { name: 'Broken' }).closest('article')!).getByRole('button', {
                name: 'Activate',
            })
        ).toBeDisabled();

        await user.click(within(card).getByRole('button', { name: 'Activate' }));

        await waitFor(() =>
            expect(requests).toHaveBeenCalledWith(
                expect.objectContaining({ method: 'post', url: '/api/admin/themes/aurora/activate' })
            )
        );
        expect(mocks.toastSuccess).toHaveBeenCalledWith('Theme activated', expect.anything());
    });

    it('previews unsaved settings on the page and puts the page back on cancel', async () => {
        document.documentElement.dataset.theme = 'aurora';
        document.documentElement.style.setProperty('--accent', 'red');
        const user = setup();
        const card = (await screen.findByRole('heading', { name: 'Aurora' })).closest('article')!;

        await user.click(within(card).getByRole('button', { name: 'Settings' }));
        const dialog = await screen.findByRole('dialog', { name: 'Aurora settings' });
        const accent = await within(dialog).findByLabelText('Accent');

        await user.clear(accent);
        await user.type(accent, '#112233');
        await user.click(within(dialog).getByRole('switch'));

        expect(document.documentElement.style.getPropertyValue('--accent')).toBe('#112233');
        expect(document.documentElement.getAttribute('data-aurora-compact')).toBe('true');

        await user.click(within(dialog).getByRole('button', { name: 'Cancel' }));

        expect(document.documentElement.style.getPropertyValue('--accent')).toBe('red');
        expect(document.documentElement.hasAttribute('data-aurora-compact')).toBe(false);
    });

    it('saves every setting and keeps the saved look on the page', async () => {
        document.documentElement.dataset.theme = 'aurora';
        const user = setup();
        const card = (await screen.findByRole('heading', { name: 'Aurora' })).closest('article')!;

        await user.click(within(card).getByRole('button', { name: 'Settings' }));
        const dialog = await screen.findByRole('dialog', { name: 'Aurora settings' });
        const accent = await within(dialog).findByLabelText('Accent');

        await user.clear(accent);
        await user.type(accent, '#445566');
        await user.click(within(dialog).getByRole('button', { name: 'Save Settings' }));

        await waitFor(() =>
            expect(requests).toHaveBeenCalledWith(
                expect.objectContaining({ method: 'patch', url: '/api/admin/themes/aurora/settings' })
            )
        );
        const patch = requests.mock.calls.find(([config]) => config.method === 'patch')![0];

        expect(JSON.parse(String(patch.data))).toEqual({ settings: { accent: '#445566', compact: false } });
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(document.documentElement.style.getPropertyValue('--accent')).toBe('#445566');
    });
});
