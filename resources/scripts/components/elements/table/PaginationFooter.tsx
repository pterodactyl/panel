import React from 'react';
import { PaginationDataSet } from '@/api/http';
import classNames from 'classnames';
import { Button } from '@/components/elements/button/index';
import { ChevronDoubleLeftIcon, ChevronDoubleRightIcon } from '@heroicons/react/solid';

interface Props {
    className?: string;
    pagination: PaginationDataSet;
    onPageSelect: (page: number) => void;
}

const PaginationFooter = ({ pagination, className, onPageSelect }: Props) => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    const end = (pagination.currentPage - 1) * pagination.perPage + pagination.count;

    const { currentPage: current, totalPages: total } = pagination;

    const pages = { previous: [] as number[], next: [] as number[] };
    for (let i = 1; i <= 2; i++) {
        if (current - i >= 1) {
            pages.previous.push(current - i);
        }
        if (current + i <= total) {
            pages.next.push(current + i);
        }
    }

    if (pagination.total === 0) {
        return null;
    }

    const buttonProps = (page: number) => ({
        size: Button.Sizes.Small,
        shape: Button.Shapes.IconSquare,
        variant: Button.Variants.Secondary,
        onClick: () => onPageSelect(page),
    });

    return (
        <div className={classNames('flex items-center justify-between my-2', className)}>
            <p className={'text-sm text-neutral-500'}>
                Showing&nbsp;
                <span className={'font-semibold text-neutral-400'}>
                    {Math.max(start, Math.min(pagination.total, 1))}
                </span>
                &nbsp;to&nbsp;
                <span className={'font-semibold text-neutral-400'}>{end}</span> of&nbsp;
                <span className={'font-semibold text-neutral-400'}>{pagination.total}</span> results.
            </p>
            {pagination.totalPages > 1 && (
                <div className={'flex space-x-1'}>
                    <Button.Text {...buttonProps(1)} disabled={pages.previous.length !== 2}>
                        <ChevronDoubleLeftIcon className={'w-3 h-3'} />
                    </Button.Text>
                    {pages.previous.reverse().map((value) => (
                        <Button.Text key={`previous-${value}`} {...buttonProps(value)}>
                            {value}
                        </Button.Text>
                    ))}
                    <Button size={Button.Sizes.Small} shape={Button.Shapes.IconSquare}>
                        {current}
                    </Button>
                    {pages.next.map((value) => (
                        <Button.Text key={`next-${value}`} {...buttonProps(value)}>
                            {value}
                        </Button.Text>
                    ))}
                    <Button.Text {...buttonProps(total)} disabled={pages.next.length !== 2}>
                        <ChevronDoubleRightIcon className={'w-3 h-3'} />
                    </Button.Text>
                </div>
            )}
        </div>
    );
};

export default PaginationFooter;
