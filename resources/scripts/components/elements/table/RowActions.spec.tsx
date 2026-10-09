/** @vitest-environment jsdom */

import type { ComponentProps } from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { afterEach, describe, expect, it, vi } from 'vitest';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';
import DataTable from './DataTable';
import { actionsColumn, DeleteAction, EditAction, EditLinkAction, RowActions, RowActionsMenu } from './RowActions';

vi.mock('@tanstack/react-router', () => ({
    Link: ({ to, params, ...props }: ComponentProps<'a'> & { to: string; params?: Record<string, number> }) => (
        <a
            href={Object.entries(params ?? {}).reduce(
                (path, [key, value]) => path.replace(`$${key}`, String(value)),
                to
            )}
            {...props}
        />
    ),
}));

interface TestRow {
    id: number;
    name: string;
}

const rows: TestRow[] = [{ id: 7, name: 'Alpha' }];

afterEach(cleanup);

describe('RowActions', () => {
    it('renders edit and delete as labelled buttons with tooltips', async () => {
        const user = userEvent.setup();
        const onEdit = vi.fn();
        const onDelete = vi.fn();

        render(
            <RowActions>
                <EditAction aria-label='Edit Alpha' onClick={onEdit} />
                <DeleteAction aria-label='Delete Alpha' onClick={onDelete} />
            </RowActions>
        );

        await user.click(screen.getByRole('button', { name: 'Edit Alpha' }));
        await user.click(screen.getByRole('button', { name: 'Delete Alpha' }));
        expect(onEdit).toHaveBeenCalledOnce();
        expect(onDelete).toHaveBeenCalledOnce();

        await user.hover(screen.getByRole('button', { name: 'Edit Alpha' }));
        await waitFor(() =>
            expect(screen.getAllByRole('tooltip').map((tooltip) => tooltip.textContent)).toContain('Edit')
        );
    });

    it('explains why a disabled action is unavailable', async () => {
        const user = userEvent.setup();
        const onDelete = vi.fn();

        render(
            <DeleteAction
                aria-label='Delete Alpha'
                disabled
                disabledReason='Users who own servers cannot be deleted.'
                onClick={onDelete}
            />
        );

        const button = screen.getByRole('button', { name: 'Delete Alpha' });

        expect(button).toBeDisabled();

        await user.hover(button.parentElement!);
        expect(await screen.findByRole('tooltip')).toHaveTextContent('Users who own servers cannot be deleted.');
    });

    it('links to the page where the resource is edited', () => {
        render(<EditLinkAction aria-label='Edit Alpha' to='/panel/nodes/$id/settings' params={{ id: 7 }} />);

        expect(screen.getByRole('link', { name: 'Edit Alpha' })).toHaveAttribute('href', '/panel/nodes/7/settings');
    });

    it('keeps other actions in the overflow menu', async () => {
        const onOpen = vi.fn();

        render(
            <RowActionsMenu label='More actions for Alpha'>
                <DropdownMenu.Item onClick={onOpen}>Open in client area</DropdownMenu.Item>
            </RowActionsMenu>
        );

        fireEvent.click(screen.getByRole('button', { name: 'More actions for Alpha' }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Open in client area' }));

        expect(onOpen).toHaveBeenCalledOnce();
    });
});

describe('actionsColumn', () => {
    it('sizes the column for its icon buttons unless a width is given', () => {
        expect(actionsColumn<TestRow>(1, () => null).meta?.cellClassName).toBe('w-14 text-right');
        expect(actionsColumn<TestRow>(2, () => null).meta?.headerClassName).toBe('w-24 text-right');
        expect(actionsColumn<TestRow>(3, () => null).meta?.cellClassName).toBe('w-32 text-right');
        expect(actionsColumn<TestRow>(2, () => null, 'w-52').meta?.cellClassName).toBe('w-52 text-right');
    });

    it('renders an unsortable column with a screen-reader header and the row actions', () => {
        const columns = [
            { accessorKey: 'name', header: 'Name' },
            actionsColumn<TestRow>(1, (row) => <EditAction aria-label={`Edit ${row.name}`} />),
        ] satisfies ColumnDef<TestRow>[];
        const Table = () => {
            const table = useReactTable({ data: rows, columns, getCoreRowModel: getCoreRowModel() });

            return <DataTable table={table} emptyState='No rows' />;
        };

        render(<Table />);

        expect(columns[1]!.enableSorting).toBe(false);
        expect(screen.getByRole('columnheader', { name: 'Actions' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Edit Alpha' })).toBeInTheDocument();
    });
});
