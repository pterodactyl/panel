import { afterEach, expect, test } from 'vitest';
import { Alert, Button, Card, Stat } from '@pterodactyl/sdk';
import { renderWithTheme, type RenderedView } from '@pterodactyl/sdk/testing';
import theme from './theme';

const themes = [theme];
let rendered: RenderedView | undefined;

afterEach(() => rendered?.unmount());

test('replaces the button root and keeps its state attributes', () => {
    rendered = renderWithTheme(<Button color='red'>Delete</Button>, { themes });

    const button = rendered.container.querySelector('button');

    expect(button?.className).toContain('rounded-full');
    expect(button?.dataset).toMatchObject({ ui: 'button', part: 'root', color: 'red' });
});

test('adds a ring to the card the layers below draw, replacing their corners', () => {
    rendered = renderWithTheme(<Card title='Resources'>Usage</Card>, { themes });

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
    rendered = renderWithTheme(<Stat label='Memory' value='1.4 GiB' limit='/ 2 GiB' />, { themes });

    expect(rendered.container.querySelector('[data-ui="stat"][data-part="value"]')?.className).toContain(
        'tabular-nums'
    );
});

test('draws alerts with the replacement component', () => {
    rendered = renderWithTheme(
        <Alert type='warning' title='Heads up'>
            Maintenance
        </Alert>,
        { themes }
    );

    expect(rendered.container.querySelector('[role="alert"]')?.className).toContain('border-l-4');
});
