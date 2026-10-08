import type { Column, RowData } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { cn } from '@/lib/cn';

interface Props<TData extends RowData, TValue> {
    column: Column<TData, TValue>;
    title: string;
    className?: string;
}

const sortIcons = { asc: ArrowUp, desc: ArrowDown };

export default function DataTableColumnHeader<TData extends RowData, TValue>({
    column,
    title,
    className,
}: Props<TData, TValue>) {
    if (!column.getCanSort()) {
        return <span className={className}>{title}</span>;
    }

    const direction = column.getIsSorted();
    const nextDirection = column.getNextSortingOrder();
    const label = nextDirection
        ? `Sort ${title} ${nextDirection === 'asc' ? 'ascending' : 'descending'}`
        : `Clear ${title} sorting`;
    const SortIcon = direction ? sortIcons[direction] : ArrowUpDown;

    return (
        <button
            type='button'
            aria-label={label}
            onClick={column.getToggleSortingHandler()}
            className={cn(
                'group -ml-1 inline-flex h-8 items-center gap-1 rounded-sm px-1 text-left [text-transform:inherit] transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
                direction && 'text-foreground',
                className
            )}
        >
            <span>{title}</span>
            <SortIcon
                aria-hidden='true'
                className={cn(
                    'h-3 w-3 shrink-0 transition-opacity',
                    direction ? 'opacity-100' : 'opacity-0 group-hover:opacity-70 group-focus-visible:opacity-70'
                )}
            />
        </button>
    );
}
