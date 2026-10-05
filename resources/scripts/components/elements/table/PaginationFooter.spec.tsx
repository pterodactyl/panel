/** @vitest-environment jsdom */

import { cleanup, render } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import PaginationFooter from './PaginationFooter';

afterEach(cleanup);

const summary = (container: HTMLElement) => container.querySelector('p')?.textContent?.replace(/\s+/g, ' ');

describe('PaginationFooter', () => {
    it('reports a 1-based range for the current page', () => {
        const { container } = render(
            <PaginationFooter
                pagination={{ total: 60, count: 25, perPage: 25, currentPage: 2, totalPages: 3 }}
                onPageSelect={vi.fn()}
            />
        );

        expect(summary(container)).toBe('Showing 26 to 50 of 60 results.');
    });

    it('reports the first and partial last pages', () => {
        const first = render(
            <PaginationFooter
                pagination={{ total: 60, count: 25, per_page: 25, current_page: 1, total_pages: 3 }}
                onPageSelect={vi.fn()}
            />
        );
        expect(summary(first.container)).toBe('Showing 1 to 25 of 60 results.');
        first.unmount();

        const last = render(
            <PaginationFooter
                pagination={{ total: 60, count: 10, perPage: 25, currentPage: 3, totalPages: 3 }}
                onPageSelect={vi.fn()}
            />
        );
        expect(summary(last.container)).toBe('Showing 51 to 60 of 60 results.');
    });

    it('reports an empty range for a page past the end', () => {
        const { container } = render(
            <PaginationFooter
                pagination={{ total: 60, count: 0, perPage: 25, currentPage: 4, totalPages: 3 }}
                onPageSelect={vi.fn()}
            />
        );

        expect(summary(container)).toBe('Showing 0 to 0 of 60 results.');
    });

    it('only shows the summary when there is a single page', () => {
        const { container, queryAllByRole } = render(
            <PaginationFooter
                pagination={{ total: 4, count: 4, perPage: 25, currentPage: 1, totalPages: 1 }}
                onPageSelect={vi.fn()}
            />
        );

        expect(summary(container)).toBe('Showing 1 to 4 of 4 results.');
        expect(queryAllByRole('button')).toHaveLength(0);
    });

    it('keeps the pager on a page past a single page of results', () => {
        const onPageSelect = vi.fn();
        const { getByRole } = render(
            <PaginationFooter
                pagination={{ total: 4, count: 0, perPage: 25, currentPage: 2, totalPages: 1 }}
                onPageSelect={onPageSelect}
            />
        );

        getByRole('button', { name: '1' }).click();

        expect(onPageSelect).toHaveBeenCalledWith(1);
    });
});
