/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import type { ComponentPartProps, ReplacementProps } from '@/extensions/componentTypes';
import {
    createComponentTestHost,
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
    type ComponentTestHost,
} from './testing';

let host: ComponentTestHost<'server.files.editor'> | ComponentTestHost<'server.files.manager'> | undefined;
afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

it('renders an editor replacement around native parts with a fixture model', () => {
    const save = vi.fn(async () => true);
    const change = vi.fn();
    function Buffer({ model }: ComponentPartProps<'server.files.editor'>) {
        return (
            <textarea
                aria-label={model.name}
                defaultValue={model.content}
                onChange={(event) => model.change(event.target.value)}
            />
        );
    }
    function Editor({ Default }: ReplacementProps<'server.files.editor'>) {
        return <Default className='custom-editor' parts={{ editor: Buffer }} />;
    }
    const mounted = createComponentTestHost('server.files.editor', {
        model: createTestFileEditor({ name: '.pteroignore', content: 'logs/*', save, change }),
    });
    host = mounted;
    render(<Editor {...mounted.props} />, { wrapper: mounted.Wrapper });

    expect(screen.getByLabelText('.pteroignore')).toHaveValue('logs/*');
    expect(screen.getByText(/excluded from backups/)).toBeVisible();
    fireEvent.change(screen.getByLabelText('.pteroignore'), { target: { value: 'cache/*' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save Content' }));

    expect(change).toHaveBeenCalledWith('cache/*');
    expect(save).toHaveBeenCalledTimes(1);
});

it('hides the native save action from a read-only editor fixture', () => {
    const mounted = createComponentTestHost('server.files.editor', {
        model: createTestFileEditor({ readOnly: true }),
    });
    host = mounted;
    const { actions: Actions } = mounted.props.parts;
    render(<Actions model={mounted.props.model} />, { wrapper: mounted.Wrapper });

    expect(screen.queryByRole('button')).not.toBeInTheDocument();
});

it('renders the native file browser from a fixture model without issuing core queries', async () => {
    const select = vi.fn();
    const model = createTestFileManager({
        directory: '/config',
        entries: [
            createTestFileManagerEntry({ name: 'plugins', path: '/config/plugins', kind: 'directory', mimetype: '' }),
            createTestFileManagerEntry({ name: 'app.yml', path: '/config/app.yml', mimetype: 'text/plain' }),
        ],
        selection: ['app.yml'],
    });
    const mounted = createComponentTestHost('server.files.manager', {
        model: { ...model, actions: { ...model.actions, select } },
    });
    host = mounted;
    function Browser({ Default }: ReplacementProps<'server.files.manager'>) {
        return <Default />;
    }
    render(<Browser {...mounted.props} />, { wrapper: mounted.Wrapper });

    expect(await screen.findByRole('link', { name: 'app.yml' })).toHaveAttribute(
        'href',
        '/server/test-server/files/edit#/config/app.yml'
    );
    expect(screen.getByRole('link', { name: 'plugins' })).toHaveAttribute(
        'href',
        '/server/test-server/files#/config/plugins'
    );
    expect(screen.getByRole('button', { name: 'New file' })).toBeVisible();
    expect(await screen.findByRole('button', { name: 'Archive' })).toBeVisible();

    fireEvent.click(screen.getAllByRole('checkbox')[0]);
    expect(select).toHaveBeenCalledWith(['plugins', 'app.yml']);
});
