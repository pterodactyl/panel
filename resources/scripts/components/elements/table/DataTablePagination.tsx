import React from 'react';
import type { RowData, Table as TanStackTable } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import Button from '@/components/elements/Button';
import { cn } from '@/lib/cn';

interface Props<TData extends RowData> {
    table: TanStackTable<TData>;
    total: number;
    count: number;
    itemLabel?: string;
    className?: string;
}

const PageButton = ({ label, children, ...props }: React.ComponentProps<typeof Button> & { label: string }) => (
    <Button.Text
        type='button'
        size='xsmall'
        isSecondary
        aria-label={label}
        title={label}
        className='inline-flex h-8 w-8 items-center justify-center p-0'
        {...props}
    >
        {children}
    </Button.Text>
);

export default function DataTablePagination<TData extends RowData>({
    table,
    total,
    count,
    itemLabel = 'items',
    className,
}: Props<TData>) {
    const { pageIndex, pageSize } = table.getState().pagination;
    const pageCount = table.getPageCount();
    const start = total === 0 ? 0 : pageIndex * pageSize + 1;
    const end = total === 0 ? 0 : Math.min(total, start + count - 1);
    const showPager = pageCount > 1 || pageIndex > 0;

    if (total === 0) {
        return null;
    }

    return (
        <div className={cn('mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between', className)}>
            <p
                aria-label={`Showing ${start} to ${end} of ${total} ${itemLabel}`}
                className='text-xs text-muted-foreground'
            >
                Showing <span className='font-medium text-foreground'>{start}</span>–
                <span className='font-medium text-foreground'>{end}</span> of{' '}
                <span className='font-medium text-foreground'>{total}</span> {itemLabel}
            </p>
            {showPager ? (
                <div className='flex items-center gap-1'>
                    <PageButton
                        label='First page'
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.setPageIndex(0)}
                    >
                        <ChevronsLeft aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <PageButton
                        label='Previous page'
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.previousPage()}
                    >
                        <ChevronLeft aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <span
                        aria-label={`Page ${pageIndex + 1} of ${Math.max(pageCount, 1)}`}
                        aria-live='polite'
                        className='min-w-24 px-2 text-center text-xs text-muted-foreground'
                    >
                        Page <span className='font-medium text-foreground'>{pageIndex + 1}</span> of{' '}
                        <span className='font-medium text-foreground'>{Math.max(pageCount, 1)}</span>
                    </span>
                    <PageButton label='Next page' disabled={!table.getCanNextPage()} onClick={() => table.nextPage()}>
                        <ChevronRight aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <PageButton
                        label='Last page'
                        disabled={!table.getCanNextPage()}
                        onClick={() => table.setPageIndex(Math.max(pageCount - 1, 0))}
                    >
                        <ChevronsRight aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                </div>
            ) : null}
        </div>
    );
}
