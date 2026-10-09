import { describe, expect, it, vi } from 'vitest';
import { fetchAllPages } from './pagination';

const list = (current: number, totalPages: number, data: number[], total = data.length) => ({
    object: 'list' as const,
    data,
    meta: { pagination: { total, count: data.length, per_page: 2, current_page: current, total_pages: totalPages } },
});

describe('fetchAllPages', () => {
    it('returns a single page as it was received', async () => {
        const fetchPage = vi.fn(async (page: number) => list(page, 1, [1, 2]));

        await expect(fetchAllPages(fetchPage, new AbortController().signal)).resolves.toEqual(list(1, 1, [1, 2]));
        expect(fetchPage.mock.calls).toEqual([[1]]);
    });

    it('returns an empty list that reports no pages', async () => {
        const fetchPage = vi.fn(async (page: number) => list(page, 0, []));

        const result = await fetchAllPages(fetchPage, new AbortController().signal);

        expect(result.data).toEqual([]);
        expect(result.meta.pagination).toMatchObject({ total: 0, count: 0, current_page: 1, total_pages: 1 });
        expect(fetchPage).toHaveBeenCalledOnce();
    });

    it('requests the pages in order and merges them into one complete page', async () => {
        const pages = [[1, 2], [3, 4], [5]];
        const fetchPage = vi.fn(async (page: number) => list(page, 3, pages[page - 1] ?? [], 5));

        const result = await fetchAllPages(fetchPage, new AbortController().signal);

        expect(fetchPage.mock.calls).toEqual([[1], [2], [3]]);
        expect(result).toEqual({
            object: 'list',
            data: [1, 2, 3, 4, 5],
            meta: { pagination: { total: 5, count: 5, per_page: 5, current_page: 1, total_pages: 1 } },
        });
    });

    it('does not request anything once aborted', async () => {
        const controller = new AbortController();

        controller.abort();
        const fetchPage = vi.fn(async (page: number) => list(page, 1, [1]));

        await expect(fetchAllPages(fetchPage, controller.signal)).rejects.toThrow();
        expect(fetchPage).not.toHaveBeenCalled();
    });

    it('stops before the next page when aborted during a request', async () => {
        const controller = new AbortController();
        const fetchPage = vi.fn(async (page: number) => {
            controller.abort();

            return list(page, 3, [page]);
        });

        await expect(fetchAllPages(fetchPage, controller.signal)).rejects.toThrow();
        expect(fetchPage).toHaveBeenCalledOnce();
    });

    it('rejects a repeated page instead of looping', async () => {
        const fetchPage = vi.fn(async () => list(1, 2, [1]));

        await expect(fetchAllPages(fetchPage, new AbortController().signal)).rejects.toThrow('pagination');
        expect(fetchPage).toHaveBeenCalledTimes(2);
    });

    it('rejects a page count that is not finite', async () => {
        const fetchPage = vi.fn(async (page: number) => list(page, Number.NaN, [page]));

        await expect(fetchAllPages(fetchPage, new AbortController().signal)).rejects.toThrow('pagination');
        expect(fetchPage).toHaveBeenCalledOnce();
    });
});
