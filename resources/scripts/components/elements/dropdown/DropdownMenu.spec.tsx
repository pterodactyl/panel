/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, expect, it } from 'vitest';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import DropdownMenu from './DropdownMenu';

let host: ExtensionTestHost | undefined;

afterEach(() => {
    cleanup();
    host?.dispose();
});

it('renders an item with a destination as a link that navigates and closes the menu', async () => {
    host = createExtensionTestHost({ path: '/' });

    await act(async () => {
        render(
            <DropdownMenu triggerContent='Account menu'>
                <DropdownMenu.Item destination={{ to: '/account/api' }}>API Credentials</DropdownMenu.Item>
            </DropdownMenu>,
            { wrapper: host!.Wrapper }
        );
    });
    fireEvent.click(await screen.findByRole('button', { name: 'Account menu' }));

    const link = await screen.findByRole('menuitem', { name: 'API Credentials' });

    expect(link.tagName).toBe('A');
    expect(link).toHaveAttribute('href', '/account/api');
    expect(link).toHaveAttribute('data-ui', 'dropdown-menu-item');

    fireEvent.click(link);
    await waitFor(() => expect(host!.location().pathname).toBe('/account/api'));
    await waitFor(() => expect(screen.queryByRole('menuitem')).not.toBeInTheDocument());
});
