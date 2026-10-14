import React from 'react';
import Button from '@/components/elements/Button';
import { ChevronsLeft, ChevronsRight } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import { useSkin, type SkinBinding } from '@/themes/skins';

interface RenderFuncProps<T> {
    items: T[];
    isLastPage: boolean;
    isFirstPage: boolean;
}

export interface PaginationProps<T> {
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

const PageButton = ({
    ui,
    current,
    ...props
}: { ui: SkinBinding<'Pagination'>; current: boolean } & React.ComponentProps<typeof Button>) => (
    <Button
        type='button'
        className={ui.classes('page', undefined, { current })}
        isSecondary={!current}
        color='primary'
        {...props}
    />
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

function Pagination<T>({ data, onPageSelect, children }: PaginationProps<T>) {
    const ui = useSkin('Pagination', {});
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
                <div {...ui('root')}>
                    {pages[0] > 1 && !isFirstPage && (
                        <PageButton ui={ui} current={false} onClick={() => onPageSelect(1)}>
                            <Icon icon={ChevronsLeft} />
                        </PageButton>
                    )}
                    {pages.map((page) => (
                        <PageButton
                            key={`block_page_${page}`}
                            ui={ui}
                            current={pagination.currentPage === page}
                            onClick={() => onPageSelect(page)}
                        >
                            {page}
                        </PageButton>
                    ))}
                    {pages[4] < pagination.totalPages && !isLastPage && (
                        <PageButton ui={ui} current={false} onClick={() => onPageSelect(pagination.totalPages)}>
                            <Icon icon={ChevronsRight} />
                        </PageButton>
                    )}
                </div>
            )}
        </>
    );
}

export default Pagination;
