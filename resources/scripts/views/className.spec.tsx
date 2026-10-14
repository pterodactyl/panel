/** @vitest-environment jsdom */
import { afterAll, afterEach, beforeAll, expect, it } from 'vitest';
import http from '@/api/http';
import { renderView, type RenderedView } from '@/sdk/testing';
import { getViewChain, getViewDefinitions, type ViewName } from './registry';

const requests: string[] = [];
const adapter = http.defaults.adapter;
let rendered: RenderedView | undefined;

beforeAll(() => {
    http.defaults.adapter = async (config) => {
        requests.push(config.url ?? '');

        throw new Error(`A fixture requested ${config.url}.`);
    };
});

afterEach(() => {
    rendered?.unmount();
    rendered = undefined;
    requests.length = 0;
});

afterAll(() => {
    http.defaults.adapter = adapter;
});

// Every fixture of every view classic draws.
const cases = getViewDefinitions().flatMap((definition) =>
    getViewChain(definition.name).some((layer) => layer.source === 'classic')
        ? Object.keys(definition.fixtures ?? {}).map((fixture) => [definition.name, fixture] as const)
        : []
);

it.each(cases)('%s (%s) renders without a backend, its className on its outermost element', async (name, fixture) => {
    rendered = renderView(name as ViewName, { fixture, className: 'probe-root' });

    const root = rendered.container.firstElementChild;

    // A view may render nothing in the page itself, such as a dialog.
    if (root) {
        expect(root.classList.contains('probe-root'), root.outerHTML.slice(0, 160)).toBe(true);
    }

    // Requests start from effects, after the render.
    await new Promise((resolve) => setTimeout(resolve, 0));
    expect(requests).toEqual([]);
});
