// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import type { AdminServer } from '@/api/admin/servers/queries';
import ServerDeleteTab from '@/components/admin/servers/ServerDeleteTab';

const server = { object: 'server', attributes: { id: 7, node: 3, name: 'Alpha' } } as AdminServer;
const mocks = vi.hoisted(() => ({ navigate: vi.fn(async () => {}) }));

vi.mock('@tanstack/react-router', () => ({ useNavigate: () => mocks.navigate }));
vi.mock('@/components/admin/servers/useServerDetail', () => ({ useServerDetail: () => ({ server, reload: vi.fn() }) }));
vi.mock('sonner', () => ({ toast: { success: vi.fn() } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;

describe('server delete tab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        adapter.mockReset();
        adapter.mockImplementation(async (config) => ({ config, headers: {}, status: 204, statusText: '', data: '' }));
        http.defaults.adapter = adapter;
        const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });

        render(
            <QueryClientProvider client={client}>
                <ServerDeleteTab />
            </QueryClientProvider>
        );
    });

    afterEach(() => {
        cleanup();
        http.defaults.adapter = originalAdapter;
    });

    it('confirms each delete mode separately and clears the confirmation on cancel', async () => {
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: 'Safely Delete This Server' }));
        const safe = await screen.findByRole('dialog', { name: 'Confirm server deletion' });

        await user.type(within(safe).getByLabelText('Confirm Server Name'), 'Alpha');
        await user.click(within(safe).getByRole('button', { name: 'Cancel' }));
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());

        await user.click(screen.getByRole('button', { name: 'Forcibly Delete This Server' }));
        const force = await screen.findByRole('dialog', { name: 'Forcibly delete server' });

        expect(within(force).getByLabelText('Confirm Server Name')).toHaveValue('');
        await user.click(within(force).getByRole('button', { name: 'Force Delete Server' }));
        expect(adapter).not.toHaveBeenCalled();
        await user.click(within(force).getByRole('button', { name: 'Cancel' }));
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());

        await user.click(screen.getByRole('button', { name: 'Safely Delete This Server' }));
        const reopened = await screen.findByRole('dialog', { name: 'Confirm server deletion' });

        expect(within(reopened).getByLabelText('Confirm Server Name')).toHaveValue('');
    });

    it('deletes with the confirmed mode and then leaves the deleted server page', async () => {
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: 'Forcibly Delete This Server' }));
        const force = await screen.findByRole('dialog', { name: 'Forcibly delete server' });

        await user.type(within(force).getByLabelText('Confirm Server Name'), 'Alpha');
        await user.click(within(force).getByRole('button', { name: 'Force Delete Server' }));

        await waitFor(() => expect(mocks.navigate).toHaveBeenCalledWith({ to: '/panel/servers' }));
        expect(adapter).toHaveBeenCalledOnce();
        expect(adapter.mock.calls[0]![0]).toMatchObject({ method: 'delete', url: '/api/admin/servers/7/force' });
    });
});
