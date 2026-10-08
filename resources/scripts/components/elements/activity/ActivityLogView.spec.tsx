/** @vitest-environment jsdom */

import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import ActivityLogView from './ActivityLogView';

afterEach(cleanup);

it('preserves both activity filters when changing pages', () => {
    const onNavigate = vi.fn();

    render(
        <ActivityLogView
            data={{
                data: [],
                meta: { pagination: { total: 50, count: 25, per_page: 25, current_page: 1, total_pages: 2 } },
            }}
            error={null}
            isFetching={false}
            refetch={vi.fn()}
            event='server:power.start'
            user='7'
            emptyMessage='No activity'
            facets={{
                events: ['server:power.start'],
                users: [{ id: 7, username: 'actor', email: 'actor@example.com' }],
            }}
            onNavigate={onNavigate}
        />
    );

    fireEvent.click(screen.getByRole('button', { name: '2' }));

    expect(onNavigate).toHaveBeenCalledWith({ page: 2, event: 'server:power.start', user: '7' });
});
