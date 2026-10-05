import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { createRequire } from 'node:module';
import { JSDOM } from 'jsdom';

const require = createRequire(import.meta.url);
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const manifest = JSON.parse(readFileSync(resolve(root, 'public/assets/manifest.json'), 'utf8'));
const dom = new JSDOM('<div id="extension-runtime-test"></div>', {
    url: 'https://panel.test/',
    pretendToBeVisual: true,
});
for (const name of [
    'window',
    'document',
    'navigator',
    'HTMLElement',
    'Element',
    'Node',
    'MutationObserver',
    'getComputedStyle',
    'requestAnimationFrame',
    'cancelAnimationFrame',
    'localStorage',
]) {
    Object.defineProperty(globalThis, name, { configurable: true, value: dom.window[name] });
}
const load = (source) => import(pathToFileURL(resolve(root, 'public/assets', manifest[source].file)).href);
const modules = [
    [
        'react',
        'resources/scripts/ext-runtime/react.ts',
        ['cache', 'cacheSignal', 'captureOwnerStack', 'unstable_useCacheRefresh'],
    ],
    ['react-dom', 'resources/scripts/ext-runtime/react-dom.ts', []],
    ['react-dom/client', 'resources/scripts/ext-runtime/react-dom-client.ts', []],
    ['react/jsx-runtime', 'resources/scripts/ext-runtime/react-jsx-runtime.ts', []],
    ['@tanstack/react-query', 'resources/scripts/ext-runtime/tanstack-react-query.ts', []],
];
try {
    for (const [specifier, source, excluded] of modules) {
        const built = await load(source);
        const installed = require(specifier);
        for (const name of Object.keys(installed).filter(
            (name) => !name.startsWith('__') && !excluded.includes(name)
        )) {
            assert.ok(name in built, `${specifier} missing built export ${name}`);
        }
        if (built.default) {
            for (const name of ['useState', 'createPortal', 'createRoot', 'jsx'].filter((name) => name in built)) {
                assert.equal(built[name], built.default[name], `${specifier} named/default export identity differs`);
            }
        }
    }
    const React = await load('resources/scripts/ext-runtime/react.ts');
    const ReactDOM = await load('resources/scripts/ext-runtime/react-dom.ts');
    const client = await load('resources/scripts/ext-runtime/react-dom-client.ts');
    const query = await load('resources/scripts/ext-runtime/tanstack-react-query.ts');
    const sdk = await load('resources/scripts/sdk/index.ts');
    const api = await load('resources/scripts/sdk/api.ts');
    assert.equal(sdk.queryClient, api.queryClient, 'SDK entries must share the panel QueryClient');
    assert.ok(sdk.queryClient instanceof query.QueryClient, 'SDK and shared Query runtime differ');
    function Consumer() {
        const [name] = React.useState('shared');
        React.useEffectEvent(() => name);
        assert.equal(query.useQueryClient(), sdk.queryClient);
        return React.createElement(React.Activity, { mode: 'visible' }, React.createElement(sdk.Button, null, name));
    }
    const container = document.getElementById('extension-runtime-test');
    const mounted = client.createRoot(container);
    ReactDOM.flushSync(() =>
        mounted.render(
            React.createElement(query.QueryClientProvider, { client: sdk.queryClient }, React.createElement(Consumer))
        )
    );
    assert.equal(container.textContent, 'shared');
    ReactDOM.flushSync(() => mounted.unmount());
    sdk.queryClient.clear();
    console.log('Built extension runtime: exports, React hooks, UI and shared QueryClient verified.');
} finally {
    dom.window.close();
}
