import React from 'react';
import { TableCheckbox } from '@/components/admin/AdminCheckbox';
import Spinner from '@/components/elements/Spinner';
import styled from 'styled-components/macro';
import tw from 'twin.macro';
import { PaginatedResult } from '@/api/http';

export const TableHead = ({ children }: { children: React.ReactNode }) => {
    return (
        <thead css={tw`bg-neutral-900 border-t border-b border-neutral-500`}>
            <tr>
                <th css={tw`px-6 py-2`}/>

                {children}
            </tr>
        </thead>
    );
};

export const TableHeader = ({ name }: { name: string }) => {
    return (
        <th css={tw`px-6 py-2`}>
            <span css={tw`flex flex-row items-center cursor-pointer`}>
                <span css={tw`text-xs font-medium tracking-wider uppercase text-neutral-300 whitespace-nowrap`}>{name}</span>

                <div css={tw`ml-1`}>
                    <svg fill="none" viewBox="0 0 20 20" css={tw`w-4 h-4 text-neutral-400`}>
                        <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M13 7L10 4L7 7"/>
                        <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M7 13L10 16L13 13"/>
                    </svg>
                </div>
            </span>
        </th>
    );
};

export const TableBody = ({ children }: { children: React.ReactNode }) => {
    return (
        <tbody>
            {children}
        </tbody>
    );
};

export const TableRow = ({ children }: { children: React.ReactNode }) => {
    return (
        <tr css={tw`h-12 hover:bg-neutral-600`}>
            {children}
        </tr>
    );
};

interface Props<T> {
    data: PaginatedResult<T>;
    onPageSelect: (page: number) => void;

    children: React.ReactNode;
}

const PaginationButton = styled.button<{ active?: boolean }>`
    ${tw`relative items-center px-3 py-1 -ml-px text-sm font-normal leading-5 transition duration-150 ease-in-out border border-neutral-500 focus:z-10 focus:outline-none focus:border-primary-300 inline-flex`};

    ${props => props.active ? tw`bg-neutral-500 text-neutral-50` : tw`bg-neutral-600 text-neutral-200 hover:text-neutral-300`};
`;

const PaginationArrow = styled.button`
    ${tw`relative inline-flex items-center px-1 py-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-600 text-neutral-400 hover:text-neutral-200 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-500`};
`;

export function Pagination<T> ({ data: { pagination }, onPageSelect, children }: Props<T>) {
    const isFirstPage = pagination.currentPage === 1;
    const isLastPage = pagination.currentPage >= pagination.totalPages;

    /* const pages = [];

    const start = Math.max(pagination.currentPage - 2, 1);
    const end = Math.min(pagination.totalPages, pagination.currentPage + 5);

    for (let i = start; i <= start + 3; i++) {
        pages.push(i);
    }

    for (let i = end; i >= end - 3; i--) {
        pages.push(i);
    } */

    const setPage = (page: number) => {
        if (page < 1 || page > pagination.totalPages) {
            return;
        }

        onPageSelect(page);
    };

    return (
        <>
            {children}

            <div css={tw`h-12 flex flex-row items-center w-full px-6 py-3 border-t border-neutral-500`}>
                <p css={tw`text-sm leading-5 text-neutral-400`}>
                    Showing <span css={tw`text-neutral-300`}>{((pagination.currentPage - 1) * pagination.perPage) + 1}</span> to <span css={tw`text-neutral-300`}>{((pagination.currentPage - 1) * pagination.perPage) + pagination.count}</span> of <span css={tw`text-neutral-300`}>{pagination.total}</span> results
                </p>

                { isFirstPage && isLastPage ?
                    null
                    :
                    <div css={tw`flex flex-row ml-auto`}>
                        <nav css={tw`relative z-0 inline-flex shadow-sm`}>
                            <PaginationArrow type="button" onClick={() => setPage(pagination.currentPage - 1)} css={tw`rounded-l-md`} aria-label="Previous">
                                <svg css={tw`w-5 h-5`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path clipRule="evenodd" fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
                                </svg>
                            </PaginationArrow>

                            <PaginationButton type="button" onClick={() => setPage(1)} active={pagination.currentPage === 1}>
                                1
                            </PaginationButton>

                            <PaginationButton type="button" onClick={() => setPage(2)} active={pagination.currentPage === 2}>
                                2
                            </PaginationButton>

                            <PaginationButton type="button" onClick={() => setPage(3)} active={pagination.currentPage === 3}>
                                3
                            </PaginationButton>

                            {/* <span css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-normal leading-5 border border-neutral-500 bg-neutral-600 text-neutral-200 cursor-default`}>
                                ...
                            </span>

                            <PaginationButton type="button" onClick={() => setPage(7)} css={tw`bg-neutral-600 text-neutral-200 hover:text-neutral-300`}>
                                7
                            </PaginationButton>

                            <PaginationButton type="button" onClick={() => setPage(8)} css={tw`bg-neutral-600 text-neutral-200 hover:text-neutral-300`}>
                                8
                            </PaginationButton>

                            <PaginationButton type="button" onClick={() => setPage(9)} css={tw`bg-neutral-600 text-neutral-200 hover:text-neutral-300`}>
                                9
                            </PaginationButton> */}

                            <PaginationArrow type="button" onClick={() => setPage(pagination.currentPage + 1)} css={tw`-ml-px rounded-r-md`} aria-label="Next">
                                <svg css={tw`w-5 h-5`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path clipRule="evenodd" fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/>
                                </svg>
                            </PaginationArrow>
                        </nav>
                    </div>
                }
            </div>
        </>
    );
}

export const Loading = () => {
    return (
        <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
            <Spinner size={'base'}/>
        </div>
    );
};

export const NoItems = () => {
    return (
        <div css={tw`w-full flex flex-col items-center justify-center pb-6 py-2 sm:py-8 md:py-10 px-8`}>
            <div css={tw`h-64 flex`}>
                <img src={'/assets/svgs/not_found.svg'} alt={'No Items'} css={tw`h-full select-none`}/>
            </div>

            <p css={tw`text-lg text-neutral-300 text-center font-normal sm:mt-8`}>No items could be found, it&apos;s almost like they are hiding.</p>
        </div>
    );
};

interface Params {
    checked: boolean;
    onSelectAllClick: (e: React.ChangeEvent<HTMLInputElement>) => void;

    children: React.ReactNode;
}

export const ContentWrapper = ({ checked, onSelectAllClick, children }: Params) => {
    return (
        <>
            <div css={tw`flex flex-row items-center h-12 px-6`}>
                <div css={tw`flex flex-row items-center`}>
                    <TableCheckbox
                        type={'checkbox'}
                        name={'selectAll'}
                        checked={checked}
                        onChange={onSelectAllClick}
                    />

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" css={tw`w-4 h-4 ml-1 text-neutral-200`}>
                        <path clipRule="evenodd" fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </div>

                <div css={tw`flex flex-row items-center px-2 py-1 ml-auto rounded cursor-pointer bg-neutral-600`}>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" css={tw`w-6 h-6 text-neutral-300`}>
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" css={tw`w-4 h-4 ml-1 text-neutral-200`}>
                        <path clipRule="evenodd" fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                    </svg>
                </div>
            </div>

            {children}
        </>
    );
};

export default ({ children }: { children: React.ReactNode }) => {
    return (
        <div css={tw`flex flex-col w-full`}>
            <div css={tw`rounded-lg shadow-md bg-neutral-700`}>
                {children}
            </div>
        </div>
    );
};
