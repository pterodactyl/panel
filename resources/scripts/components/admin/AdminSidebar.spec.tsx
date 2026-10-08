/** @vitest-environment jsdom */

import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { publishAreaNav } from '@/router/nav';
import AdminSidebar from './AdminSidebar';

vi.mock('@/extensions/Slot', () => ({ default: () => null }));
afterEach(cleanup);
vi.spyOn(window, 'scrollTo').mockImplementation(() => {});

async function renderSidebar(pathname: string) {
    publishAreaNav('admin', [
        { segment: '', label: 'Overview', exact: true },
        { segment: 'settings', label: 'Settings' },
        { segment: 'settings/mail', label: 'Mail' },
        { segment: 'users', label: 'Users' },
    ]);
    const root = createRootRoute({
        component: () => (
            <>
                <AdminSidebar />
                <Outlet />
            </>
        ),
    });
    const route = createRoute({
        getParentRoute: () => root,
        path: '/panel/$',
        component: () => <div>Admin screen</div>,
    });
    const router = createRouter({
        routeTree: root.addChildren([route]),
        history: createMemoryHistory({ initialEntries: [pathname] }),
        defaultHashScrollIntoView: false,
    });

    await router.load();
    render(<RouterProvider router={router} />);
    await screen.findByText('Admin screen');

    return router;
}

describe('admin sidebar', () => {
    it('labels the mobile disclosure with the most specific matching entry', async () => {
        await renderSidebar('/panel/settings/mail');

        expect(screen.getByRole('button', { name: 'Mail' })).toHaveAttribute('aria-expanded', 'false');
    });

    it('toggles the disclosure and closes it after navigation', async () => {
        const router = await renderSidebar('/panel/settings');
        const disclosure = screen.getByRole('button', { name: 'Settings' });

        fireEvent.click(disclosure);
        expect(disclosure).toHaveAttribute('aria-expanded', 'true');
        fireEvent.click(disclosure);
        expect(disclosure).toHaveAttribute('aria-expanded', 'false');
        fireEvent.click(disclosure);
        await act(async () => router.navigate({ to: '/panel/users' }));

        await waitFor(() =>
            expect(screen.getByRole('button', { name: 'Users' })).toHaveAttribute('aria-expanded', 'false')
        );
        expect(screen.getByRole('link', { name: 'Users' })).toHaveAttribute('data-status', 'active');
    });

    it('does not confuse entries with a shared path prefix', async () => {
        await renderSidebar('/panel/users-extra');

        expect(screen.getByRole('button', { name: 'Administration' })).toBeInTheDocument();
    });
});
