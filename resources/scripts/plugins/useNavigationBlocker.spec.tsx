/** @vitest-environment jsdom */
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import {
    createBrowserHistory,
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    RouterProvider,
    type RouterHistory,
} from '@tanstack/react-router';
import { afterEach, expect, it, vi } from 'vitest';
import { UNSAVED_CHANGES_MESSAGE, useNavigationBlocker, type NavigationBlockerOptions } from './useNavigationBlocker';

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
});

async function mount(
    shouldBlock: boolean | (() => boolean),
    options?: NavigationBlockerOptions,
    history: RouterHistory = createMemoryHistory({ initialEntries: ['/list', '/edit/one'], initialIndex: 1 })
) {
    function Editor() {
        useNavigationBlocker(shouldBlock, options);

        return <p>Editor</p>;
    }

    const root = createRootRoute();
    const router = createRouter({
        routeTree: root.addChildren([
            createRoute({ getParentRoute: () => root, path: '/', component: Editor }),
            createRoute({ getParentRoute: () => root, path: '/edit/$file', component: Editor }),
            createRoute({ getParentRoute: () => root, path: '/list', component: () => <p>List</p> }),
        ]),
        history,
    });

    render(<RouterProvider router={router} />);
    await screen.findByText(/Editor|List/);

    return router;
}

it('lets navigation through while nothing needs saving', async () => {
    const confirm = vi.spyOn(window, 'confirm');
    const router = await mount(false);

    await router.navigate({ to: '/list' } as never);

    expect(await screen.findByText('List')).toBeVisible();
    expect(confirm).not.toHaveBeenCalled();
});

it('asks before a link navigation and stays when the user declines', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
    const router = await mount(true, { message: 'Discard the draft?' });

    void router.navigate({ to: '/list' } as never);
    await waitFor(() => expect(confirm).toHaveBeenCalledWith('Discard the draft?'));
    expect(screen.getByText('Editor')).toBeVisible();

    confirm.mockReturnValue(true);
    void router.navigate({ to: '/list' } as never);
    expect(await screen.findByText('List')).toBeVisible();
});

it('guards the back button and reads a lazy condition at navigation time', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
    let dirty = false;

    window.history.replaceState(null, '', '/list');
    const history = createBrowserHistory();
    const router = await mount(() => dirty, undefined, history);

    await router.navigate({ to: '/edit/$file', params: { file: 'one' } } as never);
    await screen.findByText('Editor');

    dirty = true;
    window.history.back();
    await waitFor(() => expect(confirm).toHaveBeenCalledWith(UNSAVED_CHANGES_MESSAGE));
    await waitFor(() => expect(window.location.pathname).toBe('/edit/one'));
    expect(screen.getByText('Editor')).toBeVisible();

    dirty = false;
    window.history.back();
    expect(await screen.findByText('List')).toBeVisible();
    expect(confirm).toHaveBeenCalledTimes(1);
    history.destroy();
});

it('hands the transition to a custom prompt and falls back to the browser prompt when it fails', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);

    vi.spyOn(console, 'error').mockImplementation(() => {});
    const prompt = vi.fn<NonNullable<NavigationBlockerOptions['confirm']>>(async () => false);
    const router = await mount(true, { confirm: prompt });

    void router.navigate({ to: '/list' } as never);
    await waitFor(() => expect(prompt).toHaveBeenCalledTimes(1));
    expect(prompt.mock.calls[0][0]).toMatchObject({
        action: 'PUSH',
        current: { pathname: '/edit/one', params: { file: 'one' } },
        next: { pathname: '/list' },
    });
    expect(confirm).not.toHaveBeenCalled();

    prompt.mockRejectedValueOnce(new Error('dialog failed'));
    void router.navigate({ to: '/list' } as never);
    await waitFor(() => expect(confirm).toHaveBeenCalledTimes(1));
    expect(screen.getByText('Editor')).toBeVisible();
});

it.each([
    { name: 'asks before the tab closes', options: undefined, prevented: true },
    { name: 'leaves tab close alone when asked to', options: { beforeUnload: false }, prevented: false },
])('$name', async ({ options, prevented }) => {
    window.history.replaceState(null, '', '/');
    const history = createBrowserHistory();

    await mount(true, options, history);

    const event = new Event('beforeunload', { cancelable: true });

    window.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(prevented);
    history.destroy();
});
