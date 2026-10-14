import { httpErrorToHuman } from '@/api/http';

/** A page that loads its data before its blocks show: its spinner while that loads, its error if it fails. */
export interface PageStatusModel {
    /** The page's data has not loaded yet. */
    readonly loading: boolean;
    /** The data failed to load, so the page shows this message in place of its content. */
    readonly error: string | null;
    /** Loads what failed again. */
    readonly retry: () => void;
}

interface PageQuery {
    readonly data: unknown;
    readonly error: unknown;
    readonly refetch: () => unknown;
}

/** The status of a page from the queries it loads; a query that fails to refresh keeps its data on show. */
export function pageStatus(...queries: readonly PageQuery[]): PageStatusModel {
    const failed = queries.find((query) => query.error && query.data === undefined);

    return {
        loading: !failed && queries.some((query) => query.data === undefined),
        error: failed ? httpErrorToHuman(failed.error) : null,
        retry: () => {
            for (const query of queries) {
                if (query.error) {
                    void query.refetch();
                }
            }
        },
    };
}

const noop = () => {};

export const pageStatusFixtures = {
    default: (): PageStatusModel => ({ loading: false, error: null, retry: noop }),
    loading: (): PageStatusModel => ({ loading: true, error: null, retry: noop }),
    error: (): PageStatusModel => ({ loading: false, error: 'Request failed with status code 500.', retry: noop }),
};
