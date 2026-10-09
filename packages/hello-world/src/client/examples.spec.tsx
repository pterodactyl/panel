import ServerCard from './ServerCard';
import FileDetails from './FileDetails';
import FileEditor from './FileEditor';
import {
    createComponentTestHost,
    createTestServerCard,
    createTestFileDetails,
    createTestFileEditor,
} from '@pterodactyl/sdk/testing';
import { useState, type ReactNode } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ExtensionSetupContext, SubuserPermissionsSlotData } from '@pterodactyl/sdk';
import HelloWorldCard from './HelloWorldCard';
import SubuserPresets from './SubuserPresets';
import extension from './index';

import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@pterodactyl/sdk/testing';
import { SocketEvent, toast, type SdkServer } from '@pterodactyl/sdk';

let host: ExtensionTestHost | undefined;

beforeEach(() => {
    vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
});
afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    vi.restoreAllMocks();
});
function renderInHost(children: ReactNode, server?: SdkServer) {
    host = createExtensionTestHost({ server });

    return render(children, { wrapper: host.Wrapper });
}

it('registers a console slot, a permissions form slot, and a lazy server page', () => {
    const context: ExtensionSetupContext = {
        meta: { id: 'hello-world' },
        config: {},
        slots: { register: vi.fn() },
        screens: { register: vi.fn() },
        columns: { register: vi.fn() },
        components: { replace: vi.fn() },
        forms: { extend: vi.fn() },
    };

    extension.setup(context);

    expect(context.slots.register).toHaveBeenCalledWith('server.console.before', expect.any(Function));
    expect(context.slots.register).toHaveBeenCalledWith('server.users.permissions.before', SubuserPresets);
    expect(context.screens.register).toHaveBeenCalledWith('hello-world', expect.any(Function), {
        badge: expect.any(Function),
    });
});

it('shows the current user and updates local state and websocket feedback', async () => {
    const feedback = vi.spyOn(toast, 'success');
    const server = createTestServer({ identifier: 'test', name: 'Example server' });

    renderInHost(<HelloWorldCard serverName='Example server' />, server);
    await screen.findByText('Greetings this visit');

    fireEvent.click(screen.getByRole('button', { name: 'Say hello' }));
    fireEvent.click(screen.getByRole('button', { name: 'Say hello' }));
    act(() => host!.emitWebsocket(SocketEvent.STATUS, 'running'));

    expect(screen.getByText('Greetings this visit').nextElementSibling?.textContent).toBe('2');
    expect(screen.getByText('Hello, Alex! This extension is running on Example server.')).toBeTruthy();
    expect(screen.getByText('running')).toBeTruthy();
    expect(feedback).toHaveBeenLastCalledWith('Hello, Alex!');
    fireEvent.click(screen.getByRole('button', { name: 'Reset counter' }));
    expect(screen.getByText('Greetings this visit').nextElementSibling?.textContent).toBe('0');
});

it('preselects role permissions without submitting the surrounding form', async () => {
    const setPermissions = vi.fn();
    const submit = vi.fn((event) => event.preventDefault());
    const data: SubuserPermissionsSlotData = {
        mode: 'create',
        selectedPermissions: [],
        editablePermissions: ['file.read'],
        disabled: false,
        setPermissions,
    };

    renderInHost(
        <form onSubmit={submit}>
            <SubuserPresets data={data} />
        </form>
    );

    fireEvent.click(await screen.findByRole('button', { name: 'Viewer' }));

    expect(setPermissions).toHaveBeenCalledExactlyOnceWith([
        'websocket.connect',
        'file.read',
        'backup.read',
        'activity.read',
    ]);
    expect(submit).not.toHaveBeenCalled();
});

it('changes only the reinstall selection when the switch is toggled', async () => {
    function Draft() {
        const [selectedPermissions, setPermissions] = useState<readonly string[]>(['file.read']);

        return (
            <>
                <SubuserPresets
                    data={{
                        mode: 'edit',
                        selectedPermissions,
                        editablePermissions: ['file.read', 'settings.reinstall'],
                        disabled: false,
                        setPermissions,
                    }}
                />
                <output>{selectedPermissions.join(',')}</output>
            </>
        );
    }

    renderInHost(<Draft />);

    fireEvent.click(await screen.findByRole('switch'));
    expect(screen.getByRole('status').textContent).toBe('file.read,settings.reinstall');
    fireEvent.click(await screen.findByRole('switch'));
    expect(screen.getByRole('status').textContent).toBe('file.read');
});

it('disables the reinstall shortcut when it cannot be granted', async () => {
    const setPermissions = vi.fn();

    renderInHost(
        <SubuserPresets
            data={{
                mode: 'edit',
                selectedPermissions: [],
                editablePermissions: ['file.read'],
                disabled: false,
                setPermissions,
            }}
        />
    );

    await userEvent.click(await screen.findByRole('switch'));

    expect(screen.getByRole('switch').getAttribute('aria-disabled')).toBe('true');
    expect(setPermissions).not.toHaveBeenCalled();
});

it('disables every shortcut while the host form is read-only or saving', async () => {
    const setPermissions = vi.fn();

    renderInHost(
        <SubuserPresets
            data={{
                mode: 'edit',
                selectedPermissions: [],
                editablePermissions: ['settings.reinstall'],
                disabled: true,
                setPermissions,
            }}
        />
    );

    await userEvent.click(await screen.findByRole('button', { name: 'Operator' }));
    await userEvent.click(await screen.findByRole('switch'));

    expect(setPermissions).not.toHaveBeenCalled();
});

it('composes the native server card using the real SDK fixture', () => {
    const host = createComponentTestHost('dashboard.serverCard', {
        model: createTestServerCard({ name: 'Survival' }),
        prefix: 'hw',
    });

    render(<ServerCard {...host.props} />, { wrapper: host.Wrapper });
    expect(screen.getByText('Survival')).toBeTruthy();
    expect(screen.getByText('12.00 %')).toBeTruthy();
    // The prefixed utility replaces the native card's own gap instead of sitting beside it.
    const card = screen.getByText('Survival').closest('.grid');

    expect(card?.classList.contains('hw:gap-3')).toBe(true);
    expect(card?.classList.contains('gap-4')).toBe(false);
});

it('customizes only the file name while native size and timestamp remain available', () => {
    const host = createComponentTestHost('server.files.details', {
        model: createTestFileDetails({ name: 'server.properties' }),
    });

    render(<FileDetails {...host.props} />, { wrapper: host.Wrapper });
    expect(screen.getByText('server.properties').classList.contains('hw:font-medium')).toBe(true);
    expect(screen.getByText('1 KiB')).toBeTruthy();
    expect(screen.getByTitle('2026-01-01T12:00:00Z')).toBeTruthy();
});

it('reports unsaved changes above the native file editor', () => {
    const host = createComponentTestHost('server.files.editor', {
        model: createTestFileEditor({ name: 'server.properties', dirty: true }),
    });

    render(<FileEditor {...host.props} />, { wrapper: host.Wrapper });
    expect(screen.getByRole('status').textContent).toBe('server.properties has unsaved changes.');
    expect(screen.getByRole('button', { name: 'Save Content' })).toBeTruthy();
});
