/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import { createElement, type ReactElement, type ReactNode } from 'react';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { registerViewDefinition, registerViewLayer } from '@/views/registry';
import { View } from '@/views/View';
import { describeModel, modelJson } from './model';
import { isEjectable, overrideSnippet, renderingLayer } from './snippets';
import { tokensInUse } from './tokens';
import { ViewInspector } from './ViewInspector';

const copy = vi.hoisted(() => vi.fn(async () => true));

vi.mock('@/lib/clipboard', () => ({ default: copy }));

interface CardModel {
    name: string;
    open: () => void;
}

const open = vi.fn();

beforeAll(() => {
    registerViewDefinition({
        name: 'test.page',
        kind: 'page',
        context: 'app',
        use: () => ({}),
        slots: ['dashboard.before'],
    });
    registerViewDefinition({
        name: 'test.card',
        kind: 'block',
        context: 'app',
        use: (): CardModel => ({ name: 'Survival', open }),
        slots: ['dashboard.serverRow.name.after'],
    });
    registerViewLayer('classic', 'classic', {
        'test.page': () => (
            <main>
                <View name='test.card' />
            </main>
        ),
        'test.card': ({ model }: { model: CardModel }) => (
            <button type='button' onClick={model.open}>
                {`Card ${model.name}`}
            </button>
        ),
    });
    registerViewLayer('aurora', 'theme', {
        'test.card': ({ Default }: { Default: () => ReactNode }) => (
            <div className='aurora-card'>
                <Default />
            </div>
        ),
    });
});

beforeEach(() => {
    Object.assign(window, { SiteConfiguration: { theme: { inspect: true } } });
    vi.spyOn(Element.prototype, 'getBoundingClientRect').mockReturnValue(new DOMRect(10, 40, 200, 30));
});

afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    open.mockReset();
    Object.assign(window, { SiteConfiguration: undefined });
});

const page = () => render(<ViewInspector>{createElement(View, { name: 'test.page' })}</ViewInspector>);
const toggle = () =>
    act(async () => {
        fireEvent.keyDown(window, { code: 'KeyI', key: 'ˆ', altKey: true, shiftKey: true });
    });

describe('ViewInspector', () => {
    it('adds nothing at all when the server does not allow inspection', async () => {
        Object.assign(window, { SiteConfiguration: { theme: { inspect: false } } });

        const { container } = page();

        await toggle();

        expect(screen.queryByRole('button', { name: 'Inspect views' })).not.toBeInTheDocument();
        expect(container.querySelector('[data-view]')).toBeNull();
    });

    it('wraps views only while on, toggled by Alt+Shift+I or its button', async () => {
        const { container } = page();
        const button = screen.getByRole('button', { name: 'Inspect views' });

        expect(button).toHaveAttribute('aria-pressed', 'false');
        expect(container.querySelector('[data-view]')).toBeNull();

        await toggle();

        expect(await screen.findByText(/Inspecting views/)).toBeInTheDocument();
        expect([...container.querySelectorAll('[data-view]')].map((node) => node.getAttribute('data-view'))).toEqual([
            'test.page',
            'test.card',
        ]);

        fireEvent.click(button);

        expect(button).toHaveAttribute('aria-pressed', 'false');
        expect(container.querySelector('[data-view]')).toBeNull();
        expect(screen.queryByText(/Inspecting views/)).not.toBeInTheDocument();
    });

    it('outlines the view under the pointer and pins it on click instead of clicking the page', async () => {
        page();
        await toggle();
        await screen.findByText(/Inspecting views/);

        const card = screen.getByRole('button', { name: 'Card Survival' });

        fireEvent.pointerMove(card);
        expect(await screen.findByText('test.card')).toHaveTextContent('test.card · aurora');

        fireEvent.click(card);

        const panel = await screen.findByRole('complementary', { name: 'View inspector' });

        expect(open).not.toHaveBeenCalled();
        expect(within(panel).getByRole('heading', { name: 'test.card' })).toBeInTheDocument();
        expect(within(panel).getByRole('button', { name: 'test.page' })).toBeInTheDocument();
        expect(within(panel).getByText("'test.card': ({ model, Default }) => <Default />,")).toBeInTheDocument();
        expect(within(panel).getByText('dashboard.serverRow.name.after')).toBeInTheDocument();
        expect(within(panel).getByText('"Survival"')).toBeInTheDocument();
        expect(
            within(panel)
                .getAllByRole('listitem')
                .filter((item) => item.textContent?.includes('renders'))
                .map((item) => item.textContent)
        ).toEqual(['auroraThemerenders']);

        fireEvent.click(within(panel).getByRole('button', { name: 'Copy override' }));
        expect(await within(panel).findByText('Copied')).toBeInTheDocument();
        expect(copy).toHaveBeenCalledWith("'test.card': ({ model, Default }) => <Default />,");

        fireEvent.click(within(panel).getByRole('button', { name: 'test.page' }));
        expect(within(panel).getByRole('heading', { name: 'test.page' })).toBeInTheDocument();
    });

    it('unpins on Escape, then stops inspecting', async () => {
        const { container } = page();

        await toggle();
        await screen.findByText(/Inspecting views/);
        fireEvent.click(screen.getByRole('button', { name: 'Card Survival' }));
        await screen.findByRole('complementary', { name: 'View inspector' });

        fireEvent.keyDown(document, { key: 'Escape' });
        expect(screen.queryByRole('complementary', { name: 'View inspector' })).not.toBeInTheDocument();

        fireEvent.keyDown(document, { key: 'Escape' });
        expect(screen.queryByText(/Inspecting views/)).not.toBeInTheDocument();
        expect(container.querySelector('[data-view]')).toBeNull();

        fireEvent.click(screen.getByRole('button', { name: 'Card Survival' }));
        expect(open).toHaveBeenCalledTimes(1);
    });
});

describe('inspector helpers', () => {
    it('builds the override snippet and knows which views classic can eject', () => {
        expect(overrideSnippet('server.power')).toBe("'server.power': ({ model, Default }) => <Default />,");
        expect(isEjectable('dashboard.serverCard')).toBe(true);
        expect(isEjectable('page.server.backups')).toBe(false);
    });

    it('names the top layer that did not fail as the one that rendered', () => {
        const layer = (id: string) => ({ id, source: 'theme' as const, component: () => null });

        expect(renderingLayer({ layers: [layer('classic'), layer('aurora')], failed: [] })?.id).toBe('aurora');
        expect(renderingLayer({ layers: [layer('classic'), layer('aurora')], failed: ['aurora'] })?.id).toBe('classic');
    });

    it('prints models with functions, elements and cycles', () => {
        interface Cyclic {
            save(): void;
            icon: ReactElement;
            when: Date;
            self?: Cyclic;
        }
        const model: Cyclic = { save: function save() {}, icon: <svg />, when: new Date(0) };

        model.self = model;

        expect(describeModel(model)).toEqual({
            kind: 'branch',
            summary: '{4}',
            entries: [
                { key: 'save', node: { kind: 'value', text: 'ƒ save()', tone: 'function' } },
                { key: 'icon', node: { kind: 'value', text: '<svg />', tone: 'other' } },
                { key: 'when', node: { kind: 'value', text: '1970-01-01T00:00:00.000Z', tone: 'other' } },
                { key: 'self', node: { kind: 'value', text: '[circular]', tone: 'other' } },
            ],
        });
        expect(JSON.parse(modelJson({ open: function open() {}, count: 2 }))).toEqual({ open: 'ƒ open()', count: 2 });
    });

    it('collects the custom properties the rules on a view read, without Tailwind internals', () => {
        const style = document.head.appendChild(document.createElement('style'));

        style.textContent = [
            '.tone { color: var(--accent); }',
            '.row .icon { background: var(--popover); }',
            '.hover-tint:hover { border-color: var(--border); }',
            '.ring { --tw-ring: var(--tw-shadow); }',
            '.unused { color: var(--destructive); }',
        ].join('\n');
        document.body.innerHTML = "<div class='row tone hover-tint ring'><span class='icon'></span></div>";

        expect(tokensInUse([document.body.firstElementChild!]).map((token) => token.name)).toEqual([
            '--accent',
            '--border',
            '--popover',
        ]);
        style.remove();
    });
});
