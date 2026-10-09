import React from 'react';
import { cn } from '@/lib/cn';
import Button from '@/components/elements/Button';
import { ChevronsLeft, ChevronsRight } from 'lucide-react';
import Icon from '@/components/elements/Icon';

interface RenderFuncProps<T> {
    items: T[];
    isLastPage: boolean;
    isFirstPage: boolean;
}

interface Props<T> {
    data: GeneratedPaginatedResult<T>;
    showGoToLast?: boolean;
    showGoToFirst?: boolean;
    onPageSelect: (page: number) => void;
    children: (props: RenderFuncProps<T>) => React.ReactNode;
}

interface GeneratedPaginatedResult<T> {
    data: T[];
    meta: {
        pagination: {
            total: number;
            count: number;
            per_page: number;
            current_page: number;
            total_pages: number;
        };
    };
}

interface PaginationDataSet {
    total: number;
    count: number;
    perPage: number;
    currentPage: number;
    totalPages: number;
}

const Block = ({ className, ...props }: React.ComponentProps<typeof Button>) => (
    <Button type='button' className={cn('p-0 w-10 h-10 not-last-of-type:mr-2', className)} {...props} />
);

const normalizeGeneratedPagination = (
    pagination: GeneratedPaginatedResult<unknown>['meta']['pagination']
): PaginationDataSet => ({
    total: pagination.total,
    count: pagination.count,
    perPage: pagination.per_page,
    currentPage: pagination.current_page,
    totalPages: pagination.total_pages,
});

function Pagination<T>({ data, onPageSelect, children }: Props<T>) {
    const items = data.data;
    const pagination = normalizeGeneratedPagination(data.meta.pagination);
    const isFirstPage = pagination.currentPage === 1;
    const isLastPage = pagination.currentPage >= pagination.totalPages;

    const pages = [];

    const start = Math.max(pagination.currentPage - 2, 1);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 5);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return (
        <>
            {children({ items, isFirstPage, isLastPage })}
            {pages.length > 1 && (
                <div className='mt-4 flex justify-center'>
                    {pages[0] > 1 && !isFirstPage && (
                        <Block isSecondary color='primary' onClick={() => onPageSelect(1)}>
                            <Icon icon={ChevronsLeft} />
                        </Block>
                    )}
                    {pages.map((page) => (
                        <Block
                            isSecondary={pagination.currentPage !== page}
                            color='primary'
                            key={`block_page_${page}`}
                            onClick={() => onPageSelect(page)}
                        >
                            {page}
                        </Block>
                    ))}
                    {pages[4] < pagination.totalPages && !isLastPage && (
                        <Block isSecondary color='primary' onClick={() => onPageSelect(pagination.totalPages)}>
                            <Icon icon={ChevronsRight} />
                        </Block>
                    )}
                </div>
            )}
        </>
    );
}

export default Pagination;
