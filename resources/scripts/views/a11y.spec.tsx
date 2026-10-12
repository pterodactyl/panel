/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { useEffect } from 'react';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { afterEach, expect, it, vi } from 'vitest';
import { LayoutMainCheck, Main, RouteAnnouncer, SkipLink } from './a11y';

afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.restoreAllMocks();
});

it('renders one main landmark however many layouts nest', () => {
    render(
        <Main>
            <nav>Account navigation</nav>
            <Main>
                <p>Page</p>
            </Main>
        </Main>
    );

    expect(screen.getAllByRole('main')).toHaveLength(1);
    expect(screen.getByRole('main')).toHaveAttribute('id', 'main');
    expect(screen.getByRole('main')).toHaveAttribute('tabindex', '-1');
});

it('keeps the classes of a nested Main on a wrapper inside the one main landmark', () => {
    const { container } = render(
        <Main className='shell'>
            <Main>
                <Main className='mx-auto max-w-auth'>
                    <p>Sign in</p>
                </Main>
            </Main>
        </Main>
    );

    expect(screen.getAllByRole('main')).toHaveLength(1);
    expect(screen.getByRole('main')).toHaveClass('shell');
    expect(screen.getByText('Sign in').parentElement).toHaveClass('mx-auto', 'max-w-auth');
    // A nested Main without classes adds no element.
    expect(container.querySelectorAll('main div')).toHaveLength(1);
});

it('moves focus to the main content from the skip link without touching the URL hash', () => {
    window.location.hash = '#/config';
    render(
        <>
            <SkipLink />
            <button type='button'>Navigation</button>
            <Main>Content</Main>
        </>
    );

    const link = screen.getByRole('link', { name: 'Skip to content' });

    expect(document.body.querySelector('a, button')).toBe(link);
    fireEvent.click(link);
    expect(document.activeElement).toBe(screen.getByRole('main'));
    expect(window.location.hash).toBe('#/config');
});

it('focuses the main content and announces the title after navigating to another path', async () => {
    const titled = (title: string) =>
        function Page() {
            useEffect(() => {
                document.title = title;
            }, []);

            return <p>{title}</p>;
        };

    const root = createRootRoute({
        component: () => (
            <>
                <Main>
                    <Outlet />
                </Main>
                <RouteAnnouncer />
            </>
        ),
    });
    const router = createRouter({
        routeTree: root.addChildren([
            createRoute({ getParentRoute: () => root, path: '/', component: titled('Dashboard') }),
            createRoute({ getParentRoute: () => root, path: '/account', component: titled('Account') }),
        ]),
        history: createMemoryHistory({ initialEntries: ['/'] }),
    });

    render(<RouterProvider router={router} />);
    await screen.findByText('Dashboard');
    expect(document.activeElement).not.toBe(screen.getByRole('main'));

    await act(() => router.navigate({ to: '/account' }));
    await screen.findByText('Account');

    await waitFor(() => expect(document.activeElement).toBe(screen.getByRole('main')));
    expect(document.querySelector('[aria-live="polite"]')).toHaveTextContent('Account');
});

it('warns in development when a layout never renders Main', () => {
    vi.useFakeTimers();
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

    render(
        <>
            <LayoutMainCheck name='layout.app'>
                <p>No main here</p>
            </LayoutMainCheck>
            <LayoutMainCheck name='layout.account'>
                <Main>Placed</Main>
            </LayoutMainCheck>
        </>
    );
    act(() => {
        vi.advanceTimersByTime(3000);
    });

    expect(warn).toHaveBeenCalledExactlyOnceWith(expect.stringContaining('"layout.app" layout never rendered <Main>'));
});
