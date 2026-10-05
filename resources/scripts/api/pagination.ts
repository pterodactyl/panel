import { queryOptions, type QueryKey } from '@tanstack/react-query';
import type { PaginationMeta } from '@/api/generated';

/** Largest page size the API accepts (`ApiRequest::MAX_PER_PAGE`). */
export const MAX_PER_PAGE = 100;

interface PaginatedList<TItem> {
    data: TItem[];
    meta: { pagination: PaginationMeta };
}

/** Loads every page of a list and returns them as one complete single-page list. */
export const fetchAllPages = async <TItem, TList extends PaginatedList<TItem>>(
    fetchPage: (page: number) => Promise<TList>,
    signal: AbortSignal
): Promise<TList> => {
    const items: TItem[] = [];
    let page = 1;

    while (true) {
        signal.throwIfAborted();
        // eslint-disable-next-line no-await-in-loop -- Each response determines whether another page is needed.
        const list = await fetchPage(page);
        signal.throwIfAborted();
        const { current_page, total_pages, per_page, total } = list.meta.pagination;
        if (current_page !== page || !Number.isFinite(total_pages)) {
            throw new Error('List pagination did not advance as expected.');
        }
        items.push(...list.data);
        if (page >= total_pages) {
            return {
                ...list,
                data: items,
                meta: {
                    ...list.meta,
                    pagination: {
                        total: Math.max(total, items.length),
                        count: items.length,
                        per_page: Math.max(per_page, items.length),
                        current_page: 1,
                        total_pages: 1,
                    },
                },
            };
        }
        page += 1;
    }
};

export const allPagesQueryOptions = <TList extends PaginatedList<unknown>>(
    listKey: QueryKey,
    fetchPage: (page: number, signal: AbortSignal) => Promise<TList>
) =>
    queryOptions({
        queryKey: [...listKey, 'all'],
        queryFn: ({ signal }) => fetchAllPages<unknown, TList>((page) => fetchPage(page, signal), signal),
    });

export const listItems = <TItem>(list: { data: TItem[] }): TItem[] => list.data;
