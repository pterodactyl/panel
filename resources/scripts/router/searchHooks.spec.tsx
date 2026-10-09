/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { createMemoryHistory, createRootRoute, createRouter, RouterProvider } from '@tanstack/react-router';
import type { FunctionComponent } from 'react';
import { afterEach, expect, it } from 'vitest';
import { useActivitySearch, usePageSearch, useUserListSearch } from './search';

afterEach(cleanup);

function mount(Probe: FunctionComponent, path: string) {
    const router = createRouter({
        routeTree: createRootRoute({ component: Probe }),
        history: createMemoryHistory({ initialEntries: [path] }),
    });

    render(<RouterProvider router={router} />);

    return router;
}

it('reads search values through the route parsers', async () => {
    mount(
        () => <output>{JSON.stringify({ ...useActivitySearch(), page: usePageSearch() })}</output>,
        '/?user=42&page=1.5&event=login'
    );

    expect(await screen.findByText(JSON.stringify({ page: 1, event: 'login', user: '42' }))).toBeVisible();
});

it('navigates list search on the current route', async () => {
    let search!: ReturnType<typeof useUserListSearch>;
    const router = mount(() => {
        search = useUserListSearch();

        return <output>{search.sort ?? 'unsorted'}</output>;
    }, '/?filter=25565&page=3&sort=bogus');

    expect(await screen.findByText('unsorted')).toBeVisible();
    expect(search).toMatchObject({ page: 3, filter: '25565' });

    await act(async () => search.navigateToSearch({ sort: '-email', page: 1 }));

    expect(screen.getByText('-email')).toBeVisible();
    expect(router.state.location.pathname).toBe('/');
    expect(router.state.location.search).toEqual({ filter: '25565', sort: '-email' });
});
