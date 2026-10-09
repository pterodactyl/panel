// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios';
import type { ComponentProps } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { adminEggsQueryOptions } from '@/api/admin/eggs/queries';
import type { EggCatalogEntry } from '@/api/admin/eggs/catalog';
import EggCatalogContainer from '@/components/admin/eggs/EggCatalogContainer';

const mocks = vi.hoisted(() => ({ navigate: vi.fn(), notifyError: vi.fn(), toastSuccess: vi.fn() }));

vi.mock('@tanstack/react-router', () => ({
    useNavigate: () => mocks.navigate,
    Link: ({ to, ...props }: ComponentProps<'a'> & { to: string }) => <a href={to} {...props} />,
}));
vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));

const paper: EggCatalogEntry = {
    id: 'games-paper',
    name: 'Paper',
    description: 'A Minecraft server',
    category: 'games',
    source_url: 'https://eggs.pterodactyl.io/egg/games-paper',
};
const originalAdapter = http.defaults.adapter;
const request = vi.fn<(config: InternalAxiosRequestConfig) => Promise<AxiosResponse<unknown>>>();
const response = <TData,>(config: InternalAxiosRequestConfig, data: TData): AxiosResponse<TData> => ({
    data,
    status: 200,
    statusText: 'OK',
    headers: {},
    config,
});

const setup = () => {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });

    render(
        <QueryClientProvider client={queryClient}>
            <EggCatalogContainer />
        </QueryClientProvider>
    );

    return { queryClient, user: userEvent.setup() };
};

describe('egg catalog', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        request.mockReset();
        http.defaults.adapter = request;
    });

    afterEach(() => {
        cleanup();
        http.defaults.adapter = originalAdapter;
    });

    it('searches names and descriptions, filters categories, and resets pagination without refetching', async () => {
        const entries = Array.from({ length: 26 }, (_, index) => ({
            ...paper,
            id: `generic-${index}`,
            name: `Generic ${String(index).padStart(2, '0')}`,
            description: 'Generic server',
            category: 'generic',
        }));

        request.mockImplementation(async (config) => response(config, { data: [...entries, paper] }));
        const { user } = setup();

        await screen.findByRole('heading', { name: 'Generic 00' });
        expect(screen.getAllByRole('article')).toHaveLength(24);

        await user.click(screen.getByRole('button', { name: '2' }));
        expect(screen.getByRole('heading', { name: 'Paper' })).toBeInTheDocument();
        await user.type(screen.getByLabelText('Search eggs'), 'MINECRAFT');
        expect(screen.getAllByRole('article')).toHaveLength(1);
        expect(screen.getByRole('heading', { name: 'Paper' })).toBeInTheDocument();
        await user.clear(screen.getByLabelText('Search eggs'));
        await user.click(screen.getByLabelText('Category'));
        await user.click(await screen.findByRole('option', { name: 'games' }));
        expect(screen.getAllByRole('article')).toHaveLength(1);
        await user.type(screen.getByLabelText('Search eggs'), 'unmatched');
        expect(screen.getByText('No eggs match your search.')).toBeInTheDocument();
        expect(request).toHaveBeenCalledOnce();
    });

    it('imports the chosen id, prevents closing during upload, and allows a failed import to be retried', async () => {
        const error = new AxiosError('Catalog download failed');
        let failImport = (_error: Error) => {};

        const pending = new Promise<AxiosResponse<unknown>>((_resolve, reject) => {
            failImport = reject;
        });

        request
            .mockImplementationOnce(async (config) => response(config, { data: [paper] }))
            .mockImplementationOnce(() => pending)
            .mockImplementation(async (config) =>
                response(config, { object: 'egg', attributes: { id: 42, name: 'Paper' } })
            );
        const { user, queryClient } = setup();

        queryClient.setQueryData(adminEggsQueryOptions().queryKey, {
            object: 'list',
            data: [],
            meta: { pagination: { total: 0, count: 0, per_page: 100, current_page: 1, total_pages: 1 } },
        });
        await user.click(await screen.findByRole('button', { name: 'Import' }));
        const dialog = await screen.findByRole('dialog', { name: 'Import Paper' });
        const submit = within(dialog).getByRole('button', { name: 'Import Egg' });

        await user.click(submit);
        await waitFor(() => expect(request).toHaveBeenCalledTimes(2));
        expect(request.mock.calls[1]![0].url).toBe('/api/admin/eggs/catalog/import');
        expect(request.mock.calls[1]![0].data).toBe('{"catalog_id":"games-paper"}');
        expect(within(dialog).getByRole('button', { name: 'Cancel' })).toBeDisabled();
        await user.keyboard('{Escape}');
        expect(dialog).toBeInTheDocument();

        await act(async () => failImport(error));
        await waitFor(() => expect(mocks.notifyError).toHaveBeenCalledWith(error, 'Unable to import catalog egg'));
        expect(mocks.navigate).not.toHaveBeenCalled();
        await waitFor(() => expect(submit).toBeEnabled());
        await user.click(submit);
        await waitFor(() =>
            expect(mocks.navigate).toHaveBeenCalledWith({ to: '/panel/eggs/$eggId', params: { eggId: 42 } })
        );
        expect(queryClient.getQueryState(adminEggsQueryOptions().queryKey)?.isInvalidated).toBe(true);
        expect(mocks.toastSuccess).toHaveBeenCalledWith('Egg imported', expect.anything());
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('recovers from a catalog outage and refreshes the remote index on request', async () => {
        request
            .mockRejectedValueOnce(new AxiosError('Catalog unavailable'))
            .mockImplementationOnce(async (config) => response(config, { data: [paper] }))
            .mockImplementationOnce(async (config) => response(config, undefined))
            .mockImplementation(async (config) => response(config, { data: [] }));
        const { user } = setup();

        expect(await screen.findByRole('alert')).toHaveTextContent('Catalog unavailable');
        await user.click(screen.getByRole('button', { name: 'Retry' }));
        await screen.findByRole('heading', { name: 'Paper' });
        await user.click(screen.getByRole('button', { name: 'Refresh Catalog' }));
        await screen.findByText('The catalog is empty');
        expect(request.mock.calls.map(([config]) => [config.method, config.url])).toEqual([
            ['get', '/api/admin/eggs/catalog'],
            ['get', '/api/admin/eggs/catalog'],
            ['post', '/api/admin/eggs/catalog/refresh'],
            ['get', '/api/admin/eggs/catalog'],
        ]);
    });
});
