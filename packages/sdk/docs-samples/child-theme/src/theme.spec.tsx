import { afterEach, beforeAll, expect, test } from 'vitest';
import { Button } from '@pterodactyl/sdk';
import { preloadTheme, renderView, renderWithTheme, type RenderedView } from '@pterodactyl/sdk/testing';
import aurora from 'aurora/dist/client.js';
import theme from './theme';

// #region tests
// The chain the panel stacks over classic: Aurora, then this theme.
const themes = [aurora, theme];
let rendered: RenderedView | undefined;

beforeAll(() => preloadTheme(themes));
afterEach(() => rendered?.unmount());

test("frames Aurora's server card", () => {
    rendered = renderView('dashboard.serverCard', { themes, fixture: 'running' });

    // `Default` is Aurora's card, not classic's.
    expect(rendered.container.querySelector('.border-l-2 > .space-y-3 .font-mono')).not.toBeNull();
});

test("adds a shadow to Aurora's pill buttons", () => {
    rendered = renderWithTheme(<Button>Save</Button>, { themes });

    expect(rendered.container.querySelector('button')?.className).toContain('rounded-full');
    expect(rendered.container.querySelector('button')?.className).toContain('shadow-primary/20');
});
// #endregion tests
