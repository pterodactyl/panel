import { createContext } from 'react';

export interface Model {
    relationships: Record<string, unknown>;
}

export type UUID = string;

/**
 * Marks the provided relationships keys as present in the given model
 * rather than being optional to improve typing responses.
 */
export type WithRelationships<M extends Model, R extends string> = Omit<M, 'relationships'> & {
    relationships: Omit<M['relationships'], keyof R> & {
        [K in R]: NonNullable<M['relationships'][K]>;
    };
};

/**
 * Helper type that allows you to infer the type of an object by giving
 * it the specific API request function with a return type. For example:
 *
 * type EggT = InferModel<typeof getEgg>;
 */
export type InferModel<T extends (...args: any) => any> = ReturnType<T> extends Promise<infer U> ? U : T;

/**
 * Helper function that just returns the model you pass in, but types the model
 * such that TypeScript understands the relationships on it. This is just to help
 * reduce the amount of duplicated type casting all over the codebase.
 */
export const withRelationships = <M extends Model, R extends string>(model: M, ..._keys: R[]) => {
    return model as unknown as WithRelationships<M, R>;
};

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

function create<T>() {
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
