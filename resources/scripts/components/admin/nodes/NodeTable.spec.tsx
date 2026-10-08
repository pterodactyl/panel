/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getCoreRowModel, useReactTable, type SortingState } from '@tanstack/react-table';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { AdminNode } from '@/api/admin/nodes/queries';
import DataTable from '@/components/elements/table/DataTable';
import { nodeColumns, nodeColumnVisibility } from './NodeTable';
import { NodeStatusBadge } from './NodeStatusBadge';

afterEach(cleanup);

const resourceRows = [{ attributes: { memory: 1024, disk: 2048 } }] as AdminNode[];
const resourceColumnsOnly = Object.fromEntries(nodeColumns.map((column) => [column.id, column.id === 'resources']));

const HeaderTable = ({
    sorting,
    onSortingChange,
}: {
    sorting: SortingState;
    onSortingChange: (sorting: SortingState) => void;
}) => {
    const table = useReactTable<AdminNode>({
        data: resourceRows,
        columns: nodeColumns,
        getCoreRowModel: getCoreRowModel(),
        manualSorting: true,
        enableMultiSort: false,
        initialState: { columnVisibility: { ...resourceColumnsOnly, ...nodeColumnVisibility } },
        state: { sorting },
        onSortingChange: (updater) => onSortingChange(updater instanceof Function ? updater(sorting) : updater),
    });

    return <DataTable table={table} emptyState='No nodes' />;
};

describe('NodeTable', () => {
    it.each([
        ['checking', 'Checking'],
        ['online', 'Online'],
        ['offline', 'Offline'],
    ] as const)('renders the %s node status', (status, label) => {
        render(<NodeStatusBadge status={status} title={`${label} node`} />);

        expect(screen.getByRole('status')).toHaveTextContent(label);
        expect(screen.getByRole('status')).toHaveAttribute('title', `${label} node`);
    });

    it('only enables sorting for node-backed sort columns', () => {
        expect(nodeColumns.filter((column) => column.enableSorting).map((column) => column.id)).toEqual([
            'name',
            'memory',
            'disk',
            'created_at',
        ]);
    });

    it('sorts memory and disk from the merged resources header', async () => {
        const user = userEvent.setup();
        const onSortingChange = vi.fn();

        render(<HeaderTable sorting={[{ id: 'memory', desc: true }]} onSortingChange={onSortingChange} />);

        expect(screen.queryByRole('columnheader', { name: 'Memory' })).not.toBeInTheDocument();
        expect(screen.getByRole('columnheader', { name: /Memory.*Disk/ })).toHaveAttribute('aria-sort', 'descending');

        await user.click(screen.getByRole('button', { name: 'Sort Disk descending' }));

        expect(onSortingChange).toHaveBeenCalledWith([{ id: 'disk', desc: true }]);
    });
});
