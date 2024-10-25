import { debounce } from 'debounce';
import type { ChangeEvent, MouseEvent, ReactNode } from 'react';
import { useCallback, useState } from 'react';
import tw, { styled } from 'twin.macro';

import type { ListContext as TableHooks } from '@/api/admin';
import type { PaginatedResult, PaginationDataSet } from '@/api/http';
import { TableCheckbox } from '@/components/admin/AdminCheckbox';
import Input from '@/components/elements/Input';
import InputSpinner from '@/components/elements/InputSpinner';
import Spinner from '@/components/elements/Spinner';

export function useTableHooks<T>(initialState?: T | (() => T)): TableHooks<T> {
    const [page, setPage] = useState<number>(1);
    const [filters, setFilters] = useState<T | null>(initialState || null);
    const [sort, setSortState] = useState<string | null>(null);
    const [sortDirection, setSortDirection] = useState<boolean>(false);

    const setSort = (newSort: string | null) => {
        if (sort === newSort) {
            setSortDirection(!sortDirection);
        } else {
            setSortState(newSort);
            setSortDirection(false);
        }
    };

    return { page, setPage, filters, setFilters, sort, setSort, sortDirection, setSortDirection };
}

export const TableHeader = ({
    name,
    onClick,
    direction,
}: {
    name?: string;
    onClick?: (e: MouseEvent) => void;
    direction?: number | null;
}) => {
    if (!name) {
        return <th css={tw`px-6 py-2`} />;
    }

    return (
        <th css={tw`px-6 py-2`} onClick={onClick}>
            <span css={tw`flex flex-row items-center cursor-pointer`}>
                <span
                    css={tw`text-xs font-medium tracking-wider uppercase text-neutral-300 whitespace-nowrap select-none`}
                >
                    {name}
                </span>

                {direction !== undefined ? (
                    <div css={tw`ml-1`}>
                        <svg fill="none" viewBox="0 0 20 20" css={tw`w-4 h-4 text-neutral-400`}>
                            {direction === null || direction === 1 ? (
                                <path
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M13 7L10 4L7 7"
                                />
                            ) : null}
                            {direction === null || direction === 2 ? (
                                <path
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M7 13L10 16L13 13"
                                />
                            ) : null}
                        </svg>
                    </div>
                ) : null}
            </span>
        </th>
    );
};

export const TableHead = ({ children }: { children: ReactNode }) => {
    return (
        <thead css={tw`bg-neutral-900 border-t border-b border-neutral-500`}>
            <tr>
                <TableHeader />
                {children}
            </tr>
        </thead>
    );
};

export const TableBody = ({ children }: { children: ReactNode }) => {
    return <tbody>{children}</tbody>;
};

export const TableRow = ({ children }: { children: ReactNode }) => {
    return <tr css={tw`h-12 hover:bg-neutral-600`}>{children}</tr>;
};

interface Props<T> {
    data?: PaginatedResult<T>;
    onPageSelect: (page: number) => void;

    children: ReactNode;
}

const PaginationButton = styled.button<{ active?: boolean }>`
    ${tw`relative items-center px-3 py-1 -ml-px text-sm font-normal leading-5 transition duration-150 ease-in-out border border-neutral-500 focus:z-10 focus:outline-none focus:border-primary-300 inline-flex`};

    ${props =>
        props.active ? tw`bg-neutral-500 text-neutral-50` : tw`bg-neutral-600 text-neutral-200 hover:text-neutral-50`};
`;

const PaginationArrow = styled.button`
    ${tw`relative inline-flex items-center px-1 py-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-600 text-neutral-400 hover:text-neutral-50 focus:z-10 focus:outline-none focus:border-primary-300`};

    &:disabled {
        ${tw`bg-neutral-700`}
    }

    &:hover:disabled {
        ${tw`text-neutral-400 cursor-default`};
    }
`;

export function Pagination<T>({ data, onPageSelect, children }: Props<T>) {
    let pagination: PaginationDataSet;
    if (data === undefined) {
        pagination = {
            total: 0,
            count: 0,
            perPage: 0,
            currentPage: 1,
            totalPages: 1,
        };
    } else {
        pagination = data.pagination;
    }

    const setPage = (page: number) => {
        if (page < 1 || page > pagination.totalPages) {
            return;
        }

        onPageSelect(page);
    };

    const isFirstPage = pagination.currentPage === 1;
    const isLastPage = pagination.currentPage >= pagination.totalPages;

    const pages = [];

    if (pagination.totalPages < 7) {
        for (let i = 1; i <= pagination.totalPages; i++) {
            pages.push(i);
        }
    } else {
        // Don't ask me how this works, all I know is that this code will always have 7 items in the pagination,
        // and keeps the current page centered if it is not too close to the start or end.
        let start = Math.max(pagination.currentPage - 3, 1);
        const end = Math.min(
            pagination.totalPages,
            pagination.currentPage + (pagination.currentPage < 4 ? 7 - pagination.currentPage : 3),
        );

        while (start !== 1 && end - start !== 6) {
            start--;
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
    }

    return (
        <>
            {children}

            <div css={tw`h-12 flex flex-row items-center w-full px-6 py-3 border-t border-neutral-500`}>
                <p css={tw`text-sm leading-5 text-neutral-400`}>
                    Showing{' '}
                    <span css={tw`text-neutral-300`}>
                        {(pagination.currentPage - 1) * pagination.perPage + (pagination.total > 0 ? 1 : 0)}
                    </span>{' '}
                    to{' '}
                    <span css={tw`text-neutral-300`}>
                        {(pagination.currentPage - 1) * pagination.perPage + pagination.count}
                    </span>{' '}
                    of <span css={tw`text-neutral-300`}>{pagination.total}</span> results
                </p>

                {isFirstPage && isLastPage ? null : (
                    <div css={tw`flex flex-row ml-auto`}>
                        <nav css={tw`relative z-0 inline-flex shadow-sm`}>
                            <PaginationArrow
                                type="button"
                                css={tw`rounded-l-md`}
                                aria-label="Previous"
                                disabled={pagination.currentPage === 1}
                                onClick={() => setPage(pagination.currentPage - 1)}
                            >
                                <svg
                                    css={tw`w-5 h-5`}
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        clipRule="evenodd"
                                        fillRule="evenodd"
                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    />
                                </svg>
                            </PaginationArrow>

                            {pages.map(page => (
                                <PaginationButton
                                    key={page}
                                    type="button"
                                    onClick={() => setPage(page)}
                                    active={pagination.currentPage === page}
                                >
                                    {page}
                                </PaginationButton>
                            ))}

                            <PaginationArrow
                                type="button"
                                css={tw`-ml-px rounded-r-md`}
                                aria-label="Next"
                                disabled={pagination.currentPage === pagination.totalPages}
                                onClick={() => setPage(pagination.currentPage + 1)}
                            >
                                <svg
                                    css={tw`w-5 h-5`}
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        clipRule="evenodd"
                                        fillRule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    />
                                </svg>
                            </PaginationArrow>
                        </nav>
                    </div>
                )}
            </div>
        </>
    );
}

export const Loading = () => {
    return (
        <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '3rem' }}>
            <Spinner size={'base'} />
        </div>
    );
};

export const NoItems = ({ className }: { className?: string }) => {
    return (
        <div css={tw`w-full flex flex-col items-center justify-center py-6 px-8`} className={className}>
            <div css={tw`h-48 flex`}>
                <img src={'/assets/svgs/not_found.svg'} alt={'No Items'} css={tw`h-full select-none`} />
            </div>

            <p css={tw`text-lg text-neutral-300 text-center font-normal sm:mt-8`}>
                No items could be found, it&apos;s almost like they are hiding.
            </p>
        </div>
    );
};

interface Params {
    checked: boolean;
    onSelectAllClick: (e: ChangeEvent<HTMLInputElement>) => void;
    onSearch?: (query: string) => Promise<void>;

    children: ReactNode;
}

export const ContentWrapper = ({ checked, onSelectAllClick, onSearch, children }: Params) => {
    const [loading, setLoading] = useState(false);
    const [inputText, setInputText] = useState('');

    const search = useCallback(
        debounce((query: string) => {
            if (onSearch === undefined) {
                return;
            }

            setLoading(true);
            onSearch(query).then(() => setLoading(false));
        }, 200),
        [],
    );

    return (
        <>
            <div css={tw`flex flex-row items-center h-12 px-6`}>
                <div css={tw`flex flex-row items-center`}>
                    <TableCheckbox type={'checkbox'} name={'selectAll'} checked={checked} onChange={onSelectAllClick} />

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        css={tw`w-4 h-4 ml-1 text-neutral-200`}
                    >
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        />
                    </svg>
                </div>

                <div css={tw`flex flex-row items-center ml-auto`}>
                    <InputSpinner visible={loading}>
                        <Input
                            value={inputText}
                            css={tw`h-8`}
                            placeholder="Search..."
                            onChange={e => {
                                setInputText(e.currentTarget.value);
                                search(e.currentTarget.value);
                            }}
                        />
                    </InputSpinner>
                </div>
            </div>

            {children}
        </>
    );
};

export default ({ children }: { children: ReactNode }) => {
    return (
        <div css={tw`flex flex-col w-full`}>
            <div css={tw`rounded-lg shadow-md bg-neutral-700`}>{children}</div>
        </div>
    );
};
