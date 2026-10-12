/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import type { ViewPartProps, ViewProps } from '@/views/registry';
import {
    createTestFileEditor,
    createTestFileManager,
    createTestFileManagerEntry,
    createTestServerCard,
    createViewTestHost,
} from './testing';

let host: { dispose(): void } | undefined;

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

it('renders an editor layer around native parts with a fixture model', async () => {
    const save = vi.fn(async () => true);
    const change = vi.fn();

    function Buffer({ model }: ViewPartProps<'server.files.editor'>) {
        return (
            <textarea
                aria-label={model.name}
                defaultValue={model.content}
                onChange={(event) => model.change(event.target.value)}
            />
        );
    }

    function Editor({ Default }: ViewProps<'server.files.editor'>) {
        return <Default className='custom-editor' parts={{ editor: Buffer }} />;
    }

    const mounted = await createViewTestHost('server.files.editor', {
        model: createTestFileEditor({ name: '.pteroignore', content: 'logs/*', save, change }),
    });

    host = mounted;
    render(<Editor {...mounted.props} />, { wrapper: mounted.Wrapper });

    expect(await screen.findByLabelText('.pteroignore')).toHaveValue('logs/*');
    expect(screen.getByText(/excluded from backups/)).toBeVisible();
    fireEvent.change(screen.getByLabelText('.pteroignore'), { target: { value: 'cache/*' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save Content' }));

    expect(change).toHaveBeenCalledWith('cache/*');
    expect(save).toHaveBeenCalledTimes(1);
});

it('hides the native save action from a read-only editor fixture', async () => {
    const mounted = await createViewTestHost('server.files.editor', { fixture: 'readOnly' });

    host = mounted;
    const { actions: Actions } = mounted.props.parts;

    render(
        <>
            <Actions model={mounted.props.model} />
            <span>rendered</span>
        </>,
        { wrapper: mounted.Wrapper }
    );

    await screen.findByText('rendered');
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
    const mounted = await createViewTestHost('server.files.manager', {
        model: { ...model, actions: { ...model.actions, select } },
    });

    host = mounted;
    function Browser({ Default }: ViewProps<'server.files.manager'>) {
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

it('gives a server card layer the classic parts and the server it was rendered for', async () => {
    const mounted = await createViewTestHost('dashboard.serverCard', {
        model: createTestServerCard({ name: 'Survival', identifier: 'abc' }),
    });

    host = mounted;
    function Card({ Default, parts, model, server }: ViewProps<'dashboard.serverCard'>) {
        return (
            <>
                <span>{server.attributes.identifier}</span>
                <Default parts={{ metrics: () => <span>{`metrics of ${model.name}`}</span> }} />
                <parts.address model={model} server={server} />
            </>
        );
    }

    render(<Card {...mounted.props} />, { wrapper: mounted.Wrapper });

    expect(await screen.findByText('metrics of Survival')).toBeVisible();
    expect(screen.getByText('abc')).toBeVisible();
    expect(screen.getAllByText('127.0.0.1:25565')).toHaveLength(2);
});
