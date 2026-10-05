/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createMemoryHistory, createRootRoute, createRouter, RouterProvider } from '@tanstack/react-router';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type * as ServerQueries from '@/api/account/servers/queries';
import type { AccountServer } from '@/api/account/servers/queries';
import ServerRow from './ServerRow';

const useServerResourceUsage = vi.hoisted(() => vi.fn((_uuid: string, _enabled?: boolean) => ({ data: undefined })));

vi.mock('@/api/account/servers/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerQueries>()),
    useServerResourceUsage,
}));

afterEach(() => {
    cleanup();
    useServerResourceUsage.mockClear();
});

const server = (attributes: Partial<AccountServer['attributes']>): AccountServer =>
    ({
        object: 'server',
        attributes: {
            uuid: 'd3aac109-e5a0-4331-b03e-3454f7e136dc',
            identifier: 'd3aac109',
            name: 'Survival',
            description: '',
            status: null,
            is_transferring: false,
            is_node_under_maintenance: false,
            limits: { memory: 1024, swap: 0, disk: 2048, io: 500, cpu: 100, threads: null, oom_disabled: true },
            relationships: {},
            ...attributes,
        },
    }) as AccountServer;

const renderRow = async (row: AccountServer) => {
    const root = createRootRoute({ component: () => <ServerRow server={row} /> });
    const router = createRouter({ routeTree: root, history: createMemoryHistory({ initialEntries: ['/'] }) });
    render(
        <QueryClientProvider client={new QueryClient()}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    await screen.findByText(row.attributes.name);
};

describe('ServerRow', () => {
    it('shows a maintenance badge instead of loading stats when the node is under maintenance', async () => {
        await renderRow(server({ is_node_under_maintenance: true }));

        expect(screen.getByText('Under Maintenance')).toBeInTheDocument();
        expect(useServerResourceUsage).toHaveBeenCalledWith('d3aac109-e5a0-4331-b03e-3454f7e136dc', false);
    });

    it('loads stats for a server on an available node', async () => {
        await renderRow(server({}));

        expect(screen.queryByText('Under Maintenance')).not.toBeInTheDocument();
        expect(useServerResourceUsage).toHaveBeenCalledWith('d3aac109-e5a0-4331-b03e-3454f7e136dc', true);
    });
});
