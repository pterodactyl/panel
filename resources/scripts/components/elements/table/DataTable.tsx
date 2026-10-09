import React from 'react';
import { flexRender, type Column, type RowData, type Table as TanStackTable } from '@tanstack/react-table';
import { cn } from '@/lib/cn';
import { isString } from '@/lib/objects';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

declare module '@tanstack/react-table' {
    interface ColumnMeta<TData extends RowData, TValue> {
        cellClassName?: string;
        headerClassName?: string;
        /** Hidden columns whose sort state this header reports through `aria-sort`. */
        sortColumnIds?: string[];
    }
}

interface Props<TData extends RowData> {
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
}: Props<TData>) {
    const rows = table.getRowModel().rows;

    return (
        <div
            aria-busy={isFetching}
            className={cn('@container overflow-hidden rounded-sm border border-border bg-card', className)}
        >
            {rows.length === 0 ? (
                <div className={cn('flex transition-opacity', isFetching && 'opacity-55')}>
                    {isString(emptyState) ? (
                        <Empty className={emptyCompactClass}>
                            <EmptyDescription>{emptyState}</EmptyDescription>
                        </Empty>
                    ) : (
                        emptyState
                    )}
                </div>
            ) : (
                <div className='overflow-x-auto scrollbar-thin scrollbar-thumb-(--scrollbar-thumb)'>
                    <table className={cn('w-full border-collapse text-left text-sm', tableClassName)}>
                        <thead className='border-b border-border bg-muted/70'>
                            {table.getHeaderGroups().map((headerGroup) => (
                                <tr key={headerGroup.id}>
                                    {headerGroup.headers.map((header) => (
                                        <th
                                            key={header.id}
                                            scope='col'
                                            aria-sort={ariaSort(table, header.column)}
                                            className={cn(
                                                'h-10 whitespace-nowrap px-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground',
                                                header.column.columnDef.meta?.headerClassName
                                            )}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(header.column.columnDef.header, header.getContext())}
                                        </th>
                                    ))}
                                </tr>
                            ))}
                        </thead>
                        <tbody className={cn('divide-y divide-border transition-opacity', isFetching && 'opacity-55')}>
                            {rows.map((row) => (
                                <tr key={row.id} className='transition-colors hover:bg-muted/35'>
                                    {row.getVisibleCells().map((cell) => (
                                        <td
                                            key={cell.id}
                                            className={cn(
                                                'px-3 py-2.5 align-middle text-foreground',
                                                cell.column.columnDef.meta?.cellClassName
                                            )}
                                        >
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
