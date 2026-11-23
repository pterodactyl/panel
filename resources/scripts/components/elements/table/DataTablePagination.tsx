import React from 'react';
import type { RowData, Table as TanStackTable } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import Button from '@/components/elements/Button';
import { cn } from '@/lib/cn';
import { useTranslation } from '@/sdk/localization';

export type PaginationRange = {
    start: number;
    end: number;
    count: number;
};

interface Props<TData extends RowData> {
    table: TanStackTable<TData>;
    total: number;
    count: number;
    itemLabel?: string;
    summary?: (range: PaginationRange) => string;
    className?: string;
}

const valuePattern = /<value>(.*?)<\/value>/g;

const plainSummary = (summary: string) => summary.replace(valuePattern, '$1');

const highlightedSummary = (summary: string) =>
    React.Children.toArray(
        summary
            .split(valuePattern)
            .map((part, index) =>
                index % 2 === 1 ? <span className='font-medium text-foreground'>{part}</span> : part
            )
    );

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
    itemLabel,
    summary,
    className,
}: Props<TData>) {
    const { t } = useTranslation();
    const { pageIndex, pageSize } = table.getState().pagination;
    const pageCount = table.getPageCount();
    const start = total === 0 ? 0 : pageIndex * pageSize + 1;
    const end = total === 0 ? 0 : Math.min(total, start + count - 1);
    const showPager = pageCount > 1 || pageIndex > 0;

    if (total === 0) {
        return null;
    }

    const range = { start, end, count: total };
    const pageText = t('Page <value>{{page}}</value> of <value>{{pages}}</value>', {
        page: pageIndex + 1,
        pages: Math.max(pageCount, 1),
    });
    const defaultSummary = (shown: PaginationRange): string =>
        itemLabel
            ? t('Showing <value>{{start}}</value> to <value>{{end}}</value> of <value>{{total}}</value> {{items}}', {
                  start: shown.start,
                  end: shown.end,
                  total: shown.count,
                  items: itemLabel,
              })
            : t('Showing <value>{{start}}</value> to <value>{{end}}</value> of <value>{{count}}</value> items', shown);
    const text = (summary ?? defaultSummary)(range);

    return (
        <div className={cn('mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between', className)}>
            <p aria-label={plainSummary(text)} className='text-xs text-muted-foreground'>
                {highlightedSummary(text)}
            </p>
            {showPager ? (
                <div className='flex items-center gap-1'>
                    <PageButton
                        label={t('First page')}
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.setPageIndex(0)}
                    >
                        <ChevronsLeft aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <PageButton
                        label={t('Previous page')}
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.previousPage()}
                    >
                        <ChevronLeft aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <span
                        aria-label={plainSummary(pageText)}
                        aria-live='polite'
                        className='min-w-24 px-2 text-center text-xs text-muted-foreground'
                    >
                        {highlightedSummary(pageText)}
                    </span>
                    <PageButton
                        label={t('Next page')}
                        disabled={!table.getCanNextPage()}
                        onClick={() => table.nextPage()}
                    >
                        <ChevronRight aria-hidden='true' className='h-3.5 w-3.5' />
                    </PageButton>
                    <PageButton
                        label={t('Last page')}
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
