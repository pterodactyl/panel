import { afterEach, expect, test } from 'vitest';
import { renderPrimitive, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

let rendered: RenderedView | undefined;

afterEach(() => rendered?.unmount());

test('replaces the button root and keeps its state attributes', () => {
    rendered = renderPrimitive('Button', { children: 'Delete', color: 'red' }, { theme });

    const button = rendered.container.querySelector('button');

    expect(button?.className).toContain('rounded-full');
    expect(button?.dataset).toMatchObject({ ui: 'button', part: 'root', color: 'red' });
});

test('adds a ring to the card the layers below draw, replacing their corners', () => {
    rendered = renderPrimitive('Card', { title: 'Resources', children: 'Usage' }, { theme });

    const card = rendered.container.querySelector('[data-ui="card"][data-part="root"]');

    expect(card?.className).toContain('ring-1 ring-border/60');
    expect(card?.className).toContain('bg-card');
    expect(card?.classList.contains('rounded-lg')).toBe(true);
    expect(card?.classList.contains('rounded-sm')).toBe(false);
    expect(rendered.container.querySelector('[data-ui="card"][data-part="title"]')?.className).toBe(
        'text-base font-semibold text-foreground'
    );
});

test('sets stat values in tabular figures', () => {
    rendered = renderPrimitive('Stat', { label: 'Memory', value: '1.4 GiB', limit: '/ 2 GiB' }, { theme });

    expect(rendered.container.querySelector('[data-ui="stat"][data-part="value"]')?.className).toContain(
        'tabular-nums'
    );
});

test('draws alerts with the replacement component', () => {
    rendered = renderPrimitive('Alert', { type: 'warning', title: 'Heads up', children: 'Maintenance' }, { theme });

    expect(rendered.container.querySelector('[role="alert"]')?.className).toContain('border-l-4');
});
