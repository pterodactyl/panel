/** @vitest-environment jsdom */

import { useState } from 'react';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    getCoreRowModel,
    useReactTable,
    type ColumnDef,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { afterEach, describe, expect, it } from 'vitest';
import DataTable from './DataTable';
import DataTablePagination from './DataTablePagination';

interface TestRow {
    id: number;
    name: string;
}

const rows: TestRow[] = [
    { id: 1, name: 'Alpha' },
    { id: 2, name: 'Bravo' },
];

const noRows: TestRow[] = [];

const columns = [{ accessorKey: 'name', header: 'Name' }] satisfies ColumnDef<TestRow>[];

const TestTable = ({ total = 3 }: { total?: number }) => {
    const [pagination, setPagination] = useState<PaginationState>({ pageIndex: 0, pageSize: 2 });
    const table = useReactTable({
        data: rows,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (row) => String(row.id),
        manualPagination: true,
        rowCount: total,
        state: { pagination },
        onPaginationChange: setPagination,
    });

    return (
        <>
            <DataTable table={table} emptyState='No rows' />
            <DataTablePagination table={table} total={total} count={2} itemLabel='servers' />
        </>
    );
};

const mergedColumns = [
    { accessorKey: 'name', header: 'Name' },
    { id: 'size', header: 'Size', cell: () => null, meta: { sortColumnIds: ['id'] } },
    { accessorKey: 'id', header: 'ID', enableSorting: true },
] satisfies ColumnDef<TestRow>[];

const MergedSortTable = ({ sorting }: { sorting: SortingState }) => {
    const table = useReactTable({
        data: rows,
        columns: mergedColumns,
        getCoreRowModel: getCoreRowModel(),
        manualSorting: true,
        state: { sorting, columnVisibility: { id: false } },
    });

    return <DataTable table={table} emptyState='No rows' />;
};

afterEach(cleanup);

describe('DataTable', () => {
    it('renders semantic headers and rows through the TanStack row model', () => {
        render(<TestTable />);

        expect(screen.getByRole('columnheader', { name: 'Name' })).toBeInTheDocument();
        expect(screen.getByRole('cell', { name: 'Alpha' })).toBeInTheDocument();
        expect(screen.getByRole('cell', { name: 'Bravo' })).toBeInTheDocument();
    });

    it('drives controlled manual pagination through the table instance', async () => {
        const user = userEvent.setup();

        render(<TestTable />);

        await user.click(screen.getByRole('button', { name: 'Next page' }));

        expect(screen.getByLabelText('Page 2 of 2')).toBeInTheDocument();
        expect(screen.getByLabelText('Showing 3 to 3 of 3 servers')).toBeInTheDocument();
    });

    it('only shows the range summary when every row fits on one page', () => {
        render(<TestTable total={2} />);

        expect(screen.getByLabelText('Showing 1 to 2 of 2 servers')).toBeInTheDocument();
        expect(screen.queryByLabelText('Page 1 of 1')).not.toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('replaces the table with the empty state when there are no rows', () => {
        const EmptyTable = ({ isFetching }: { isFetching?: boolean }) => {
            const table = useReactTable({ data: noRows, columns, getCoreRowModel: getCoreRowModel() });

            return (
                <>
                    <DataTable table={table} isFetching={isFetching} emptyState={<p>No servers yet</p>} />
                    <DataTablePagination table={table} total={0} count={0} itemLabel='servers' />
                </>
            );
        };

        const { container, rerender } = render(<EmptyTable />);

        expect(screen.getByText('No servers yet')).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(screen.queryByRole('columnheader')).not.toBeInTheDocument();
        expect(screen.queryByLabelText(/^Showing/)).not.toBeInTheDocument();
        expect(container.firstElementChild).toHaveAttribute('aria-busy', 'false');

        rerender(<EmptyTable isFetching />);

        expect(container.firstElementChild).toHaveAttribute('aria-busy', 'true');
    });

    it('renders a plain string empty state as an empty-state description', () => {
        const StringTable = () => {
            const table = useReactTable({ data: noRows, columns, getCoreRowModel: getCoreRowModel() });

            return <DataTable table={table} emptyState='No rows' />;
        };

        render(<StringTable />);

        expect(screen.getByText('No rows')).toHaveAttribute('data-slot', 'empty-description');
    });

    it('reports the sort state of hidden sort columns on the header that controls them', () => {
        const { rerender } = render(<MergedSortTable sorting={[{ id: 'id', desc: true }]} />);

        expect(screen.getByRole('columnheader', { name: 'Size' })).toHaveAttribute('aria-sort', 'descending');
        expect(screen.queryByRole('columnheader', { name: 'ID' })).not.toBeInTheDocument();

        rerender(<MergedSortTable sorting={[]} />);

        expect(screen.getByRole('columnheader', { name: 'Size' })).not.toHaveAttribute('aria-sort');
    });
});
