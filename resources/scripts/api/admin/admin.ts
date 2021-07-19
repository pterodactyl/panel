import { createContext } from 'react';

export interface ListContext<T> {
    page: number;
    setPage: (page: ((p: number) => number) | number) => void;

    filters: T | null;
    setFilters: (filters: ((f: T | null) => T | null) | T | null) => void;

    sort: string | null;
    setSort: (sort: string | null) => void;

    sortDirection: boolean;
    setSortDirection: (direction: ((p: boolean) => boolean) | boolean) => void;
}

function create<T> () {
    return createContext<ListContext<T>>({
        page: 1,
        setPage: () => 1,

        filters: null,
        setFilters: () => null,

        sort: null,
        setSort: () => null,

        sortDirection: false,
        setSortDirection: () => false,
    });
}

export { create as createContext };
