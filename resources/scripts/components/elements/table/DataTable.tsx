import React from 'react';
import { flexRender, type Column, type RowData, type Table as TanStackTable } from '@tanstack/react-table';
import { isString } from '@/lib/objects';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { useSkin } from '@/themes/skins';

declare module '@tanstack/react-table' {
    interface ColumnMeta<TData extends RowData, TValue> {
        cellClassName?: string;
        headerClassName?: string;
        /** Hidden columns whose sort state this header reports through `aria-sort`. */
        sortColumnIds?: string[];
    }
}

export interface DataTableProps<TData extends RowData> {
    table: TanStackTable<TData>;
    /** Replaces the table when there are no rows; a string renders as a compact `Empty` description. */
    emptyState: React.ReactNode;
    className?: string;
    tableClassName?: string;
    isFetching?: boolean;
}

const ariaSort = <TData extends RowData>(
    table: TanStackTable<TData>,
    column: Column<TData, unknown>
): React.AriaAttributes['aria-sort'] => {
    const ids = column.columnDef.meta?.sortColumnIds;
    const direction = ids
        ? (ids.map((id) => table.getColumn(id)?.getIsSorted()).find(Boolean) ?? false)
        : column.getIsSorted();

    if (direction === 'asc') {
        return 'ascending';
    }

    if (direction === 'desc') {
        return 'descending';
    }

    return undefined;
};

export default function DataTable<TData extends RowData>({
    table,
    emptyState,
    className,
    tableClassName,
    isFetching = false,
}: DataTableProps<TData>) {
    const rows = table.getRowModel().rows;
    const ui = useSkin('Table', { fetching: isFetching });

    return (
        <div aria-busy={isFetching} {...ui('root', className)}>
            {rows.length === 0 ? (
                <div {...ui('empty')}>
                    {isString(emptyState) ? (
                        <Empty size='compact'>
                            <EmptyDescription>{emptyState}</EmptyDescription>
                        </Empty>
                    ) : (
                        emptyState
                    )}
                </div>
            ) : (
                <div {...ui('scroller')}>
                    <table {...ui('table', tableClassName)}>
                        <thead {...ui('head')}>
                            {table.getHeaderGroups().map((headerGroup) => (
                                <tr key={headerGroup.id} {...ui('headerRow')}>
                                    {headerGroup.headers.map((header) => (
                                        <th
                                            key={header.id}
                                            scope='col'
                                            aria-sort={ariaSort(table, header.column)}
                                            {...ui('headerCell', header.column.columnDef.meta?.headerClassName)}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(header.column.columnDef.header, header.getContext())}
                                        </th>
                                    ))}
                                </tr>
                            ))}
                        </thead>
                        <tbody {...ui('body')}>
                            {rows.map((row) => (
                                <tr key={row.id} {...ui('row')}>
                                    {row.getVisibleCells().map((cell) => (
                                        <td key={cell.id} {...ui('cell', cell.column.columnDef.meta?.cellClassName)}>
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
