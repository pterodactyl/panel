import { cn } from '@/lib/cn';
import Button from '@/components/elements/Button';
import { ChevronsLeft, ChevronsRight } from 'lucide-react';

interface PaginationDataSet {
    total: number;
    count: number;
    perPage: number;
    currentPage: number;
    totalPages: number;
}

interface Props {
    className?: string;
    pagination:
        | PaginationDataSet
        | {
              total: number;
              count: number;
              per_page: number;
              current_page: number;
              total_pages: number;
          };
    onPageSelect: (page: number) => void;
}

const PaginationFooter = ({ pagination, className, onPageSelect }: Props) => {
    const normalized =
        'per_page' in pagination
            ? {
                  total: pagination.total,
                  count: pagination.count,
                  perPage: pagination.per_page,
                  currentPage: pagination.current_page,
                  totalPages: pagination.total_pages,
              }
            : pagination;
    const offset = (normalized.currentPage - 1) * normalized.perPage;
    const start = normalized.count > 0 ? offset + 1 : 0;
    const end = normalized.count > 0 ? offset + normalized.count : 0;

    const { currentPage: current, totalPages: total } = normalized;

    const previousPages: number[] = [];
    const nextPages: number[] = [];
    for (let i = 1; i <= 2; i++) {
        if (current - i >= 1) {
            previousPages.push(current - i);
        }
        if (current + i <= total) {
            nextPages.push(current + i);
        }
    }

    if (normalized.total === 0) {
        return null;
    }

    const buttonProps = (page: number) => ({
        type: 'button' as const,
        size: 'xsmall' as const,
        isSecondary: true,
        className: 'w-8 h-8 p-0 inline-flex items-center justify-center',
        onClick: () => onPageSelect(page),
    });

    return (
        <div className={cn('flex items-center justify-between my-2', className)}>
            <p className={'text-sm text-muted-foreground'}>
                Showing&nbsp;
                <span className={'font-semibold text-muted-foreground'}>{start}</span>
                &nbsp;to&nbsp;
                <span className={'font-semibold text-muted-foreground'}>{end}</span> of&nbsp;
                <span className={'font-semibold text-muted-foreground'}>{normalized.total}</span> results.
            </p>
            {(total > 1 || current > 1) && (
                <div className={'flex space-x-1'}>
                    <Button.Text {...buttonProps(1)} aria-label={'First page'} disabled={previousPages.length !== 2}>
                        <ChevronsLeft className={'w-3 h-3'} />
                    </Button.Text>
                    {previousPages.reverse().map((value) => (
                        <Button.Text key={`previous-${value}`} {...buttonProps(value)}>
                            {value}
                        </Button.Text>
                    ))}
                    <Button
                        type={'button'}
                        size={'xsmall'}
                        aria-current={'page'}
                        className={'w-8 h-8 p-0 inline-flex items-center justify-center'}
                    >
                        {current}
                    </Button>
                    {nextPages.map((value) => (
                        <Button.Text key={`next-${value}`} {...buttonProps(value)}>
                            {value}
                        </Button.Text>
                    ))}
                    <Button.Text {...buttonProps(total)} aria-label={'Last page'} disabled={nextPages.length !== 2}>
                        <ChevronsRight className={'w-3 h-3'} />
                    </Button.Text>
                </div>
            )}
        </div>
    );
};

export default PaginationFooter;
