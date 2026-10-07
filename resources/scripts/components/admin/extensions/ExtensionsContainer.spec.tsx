// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { adminExtensionsQueryOptions } from '@/api/admin/extensions/queries';
import ExtensionsContainer from '@/components/admin/extensions/ExtensionsContainer';

const mocks = vi.hoisted(() => ({ notifyError: vi.fn(), toastSuccess: vi.fn() }));

vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));

const installed = {
    id: 'votes',
    name: 'Votes',
    version: '2.0.0',
    installed: true,
    enabled: true,
    state: 'enabled',
    error: null,
};
const originalAdapter = http.defaults.adapter;
const upload = vi.fn<(config: InternalAxiosRequestConfig) => Promise<AxiosResponse>>();

const conflict = (config: InternalAxiosRequestConfig) =>
    new AxiosError('Request failed', AxiosError.ERR_BAD_REQUEST, config, undefined, {
        status: 409,
        statusText: 'Conflict',
        headers: {},
        config,
        data: {
            errors: [
                {
                    code: 'ExtensionAlreadyInstalledException',
                    status: '409',
                    detail: 'Extension "votes" v1.0.0 is already installed; replacing it with v2.0.0 must be confirmed.',
                    meta: { identifier: 'votes', installed_version: '1.0.0', version: '2.0.0', enabled: true },
                },
            ],
        },
    });

const setup = () => {
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false, staleTime: Infinity }, mutations: { retry: false } },
    });
    queryClient.setQueryData(adminExtensionsQueryOptions().queryKey, {
        data: [],
        meta: { enabled: true, directory: '/srv/extensions' },
    });
    render(
        <QueryClientProvider client={queryClient}>
            <ExtensionsContainer />
        </QueryClientProvider>
    );

    return userEvent.setup();
};

describe('extension install dialog', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        upload.mockReset();
        http.defaults.adapter = async (config) => {
            if (config.method === 'get') {
                return {
                    data: { data: [installed], meta: { enabled: true } },
                    status: 200,
                    statusText: 'OK',
                    headers: {},
                    config,
                };
            }

            return upload(config);
        };
    });

    afterEach(() => {
        cleanup();
        http.defaults.adapter = originalAdapter;
    });

    it('names the package and asks before replacing an installed extension', async () => {
        const user = setup();
        upload
            .mockImplementationOnce(async (config) => {
                throw conflict(config);
            })
            .mockImplementationOnce(async (config) => ({
                data: { data: installed },
                status: 201,
                statusText: 'Created',
                headers: {},
                config,
            }));

        await user.click(screen.getAllByRole('button', { name: 'Install' })[0]!);
        const dialog = await screen.findByRole('dialog', { name: 'Install extension' });
        await user.upload(within(dialog).getByLabelText('Extension package'), new File(['zip'], 'votes.pteroext'));
        await user.click(within(dialog).getByRole('button', { name: 'Install' }));

        const notice = await within(dialog).findByRole('alert');
        expect(notice).toHaveTextContent('This package is votes v2.0.0, and v1.0.0 of votes is already installed.');
        expect(notice).toHaveTextContent('stays enabled');
        expect(mocks.notifyError).not.toHaveBeenCalled();
        expect((upload.mock.calls[0]![0].data as FormData).get('replace')).toBe('false');

        await user.click(within(dialog).getByRole('button', { name: 'Replace votes' }));

        await waitFor(() => expect(upload).toHaveBeenCalledTimes(2));
        expect((upload.mock.calls[1]![0].data as FormData).get('replace')).toBe('true');
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(mocks.toastSuccess).toHaveBeenCalledWith('Extension installed', expect.anything());
    });

    it('asks again when a different package is chosen', async () => {
        const user = setup();
        upload.mockImplementation(async (config) => {
            throw conflict(config);
        });

        await user.click(screen.getAllByRole('button', { name: 'Install' })[0]!);
        const dialog = await screen.findByRole('dialog', { name: 'Install extension' });
        const input = within(dialog).getByLabelText('Extension package');
        await user.upload(input, new File(['zip'], 'votes.pteroext'));
        await user.click(within(dialog).getByRole('button', { name: 'Install' }));
        await within(dialog).findByRole('button', { name: 'Replace votes' });

        await user.upload(input, new File(['zip'], 'other.pteroext'));

        expect(within(dialog).queryByRole('alert')).not.toBeInTheDocument();
        expect(within(dialog).getByRole('button', { name: 'Install' })).toBeEnabled();
    });
});
