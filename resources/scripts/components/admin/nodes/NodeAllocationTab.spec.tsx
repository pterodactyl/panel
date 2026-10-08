/** @vitest-environment jsdom */

import type { ComponentProps } from 'react';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as NodeQueries from '@/api/admin/nodes/queries';
import type { AdminAllocation, useAdminNodeAllocations } from '@/api/admin/nodes/queries';
import NodeAllocationTab from './NodeAllocationTab';

type AllocationPage = NonNullable<ReturnType<typeof useAdminNodeAllocations>['data']>;

const state = vi.hoisted(() => ({ updateAlias: vi.fn() }));
let page: AllocationPage;

vi.mock('@/api/admin/nodes/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof NodeQueries>()),
    useAdminNodeAllocations: () => ({ data: page, error: null, isFetching: false, refetch: vi.fn() }),
    useUpdateAdminNodeAllocationAlias: () => ({ mutate: state.updateAlias }),
    useCreateAdminNodeAllocations: () => ({ mutateAsync: vi.fn() }),
    useDeleteAdminNodeAllocation: () => ({ mutate: vi.fn() }),
    useBulkDeleteAdminNodeAllocations: () => ({ mutate: vi.fn() }),
    useDeleteAdminNodeIpBlockAllocations: () => ({ mutate: vi.fn() }),
}));
vi.mock('@/components/admin/nodes/useNodeDetail', () => ({
    useNodeDetail: () => ({ node: { attributes: { id: 7 } } }),
}));
vi.mock('@/router/search', () => ({
    usePageSearch: () => 1,
    getPageSearch: (page: number) => ({ page }),
}));
vi.mock('@tanstack/react-router', () => ({
    useNavigate: () => vi.fn(),
    Link: ({ to, params: _params, ...props }: ComponentProps<'a'> & { to: string; params?: object }) => (
        <a href={to} {...props} />
    ),
}));

const allocation = (id: number, port: number, alias: string | null): AdminAllocation => ({
    object: 'allocation',
    attributes: {
        id,
        ip: '10.0.0.1',
        alias,
        port,
        notes: null,
        server_id: null,
        server_name: null,
        assigned: false,
    },
});

const allocationPage = (data: AdminAllocation[]): AllocationPage => ({
    object: 'list',
    data,
    meta: { pagination: { total: data.length, count: data.length, per_page: 50, current_page: 1, total_pages: 1 } },
});

afterEach(cleanup);
beforeEach(() => {
    vi.clearAllMocks();
    page = allocationPage([allocation(1, 25565, null), allocation(2, 25566, 'proxy')]);
});

describe('NodeAllocationTab', () => {
    it('keeps an alias being typed when the selection or the list data changes', async () => {
        const user = userEvent.setup();
        const { rerender } = render(<NodeAllocationTab />);

        const input = screen.getByLabelText('Alias for 10.0.0.1:25565');

        await user.type(input, 'lobby');
        await user.click(screen.getAllByRole('checkbox')[2]!);

        expect(screen.getByLabelText('Alias for 10.0.0.1:25565')).toBe(input);
        expect(input).toHaveValue('lobby');

        page = allocationPage([allocation(1, 25565, null), allocation(2, 25566, 'bungee')]);
        rerender(<NodeAllocationTab />);

        expect(screen.getByLabelText('Alias for 10.0.0.1:25565')).toBe(input);
        expect(input).toHaveValue('lobby');
        expect(screen.getByLabelText('Alias for 10.0.0.1:25566')).toHaveValue('bungee');
    });

    it('saves the typed alias on blur and then shows the saved value', async () => {
        const user = userEvent.setup();
        const { rerender } = render(<NodeAllocationTab />);

        const input = screen.getByLabelText('Alias for 10.0.0.1:25565');

        await user.type(input, 'lobby');
        await user.tab();

        expect(state.updateAlias).toHaveBeenCalledOnce();
        const [variables, options] = state.updateAlias.mock.calls[0]!;

        expect(variables).toEqual({ path: { node_id: 7, id: 1 }, body: { alias: 'lobby' } });

        page = allocationPage([allocation(1, 25565, 'lobby-1'), allocation(2, 25566, 'proxy')]);
        rerender(<NodeAllocationTab />);
        expect(input).toHaveValue('lobby');

        options.onSuccess();
        rerender(<NodeAllocationTab />);
        expect(screen.getByLabelText('Alias for 10.0.0.1:25565')).toHaveValue('lobby-1');
    });

    it('does not save an alias that was not changed', async () => {
        const user = userEvent.setup();

        render(<NodeAllocationTab />);

        await user.click(screen.getByLabelText('Alias for 10.0.0.1:25566'));
        await user.tab();

        expect(state.updateAlias).not.toHaveBeenCalled();
    });

    it('only shows the pager when the allocations span more than one page', () => {
        const { rerender } = render(<NodeAllocationTab />);

        expect(screen.getByText('2 allocations')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Next page' })).not.toBeInTheDocument();

        page = {
            ...page,
            meta: { pagination: { total: 60, count: 2, per_page: 50, current_page: 1, total_pages: 2 } },
        };
        rerender(<NodeAllocationTab />);

        expect(screen.getByRole('button', { name: 'Next page' })).toBeEnabled();
    });
});
