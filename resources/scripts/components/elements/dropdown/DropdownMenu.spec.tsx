/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import { createExtensionTestHost, type ExtensionTestHost } from '@/sdk/testing';
import DropdownMenu from './DropdownMenu';

let host: ExtensionTestHost | undefined;

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
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

it('marks a checked item for assistive technology and skins, and closes on a pick', async () => {
    const pick = vi.fn();

    render(
        <DropdownMenu triggerContent='Scheme'>
            <DropdownMenu.Item checked onClick={() => pick('dark')}>
                Dark
            </DropdownMenu.Item>
            <DropdownMenu.Item checked={false} onClick={() => pick('light')}>
                Light
            </DropdownMenu.Item>
        </DropdownMenu>
    );
    fireEvent.click(screen.getByRole('button', { name: 'Scheme' }));

    const dark = await screen.findByRole('menuitemcheckbox', { name: 'Dark' });
    const light = screen.getByRole('menuitemcheckbox', { name: 'Light' });

    expect(dark).toHaveAttribute('aria-checked', 'true');
    expect(dark).toHaveAttribute('data-checked');
    expect(dark).toHaveClass('bg-muted');
    expect(light).toHaveAttribute('aria-checked', 'false');
    expect(light).not.toHaveClass('bg-muted');

    fireEvent.click(light);

    expect(pick).toHaveBeenCalledWith('light');
    await waitFor(() => expect(screen.queryByRole('menuitemcheckbox')).not.toBeInTheDocument());
});
