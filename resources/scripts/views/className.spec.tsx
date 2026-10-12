/** @vitest-environment jsdom */
import { afterEach, expect, it } from 'vitest';
import { renderView, type RenderedView } from '@/sdk/testing';
import { getViewChain, getViewDefinitions, type ViewName } from './registry';

let rendered: RenderedView | undefined;

afterEach(() => rendered?.unmount());

// Every fixture of every view classic draws.
const cases = getViewDefinitions().flatMap((definition) =>
    getViewChain(definition.name).some((layer) => layer.source === 'classic')
        ? Object.keys(definition.fixtures ?? {}).map((fixture) => [definition.name, fixture] as const)
        : []
);

it.each(cases)('%s (%s) puts its className on the outermost element it renders', (name, fixture) => {
    rendered = renderView(name as ViewName, { fixture, className: 'probe-root' });

    const root = rendered.container.firstElementChild;

    // A view may render nothing in the page itself, such as a closed prompt.
    if (root) {
        expect(root.classList.contains('probe-root'), root.outerHTML.slice(0, 160)).toBe(true);
    }
});
