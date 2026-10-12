/** @vitest-environment jsdom */
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ThemeBootstrap } from '@/bootstrap';
import type * as BootModule from './boot';
import type * as RegistryModule from './registry';
import type * as ThemeModule from './theme';
import type * as SkinsModule from '@/themes/skins';
import type * as ExtensionRegistry from '@/extensions/registry';
import type * as Sonner from 'sonner';

const toastError = vi.hoisted(() => vi.fn());

vi.mock('sonner', async (importOriginal) => ({
    ...(await importOriginal<typeof Sonner>()),
    toast: Object.assign(vi.fn(), { error: toastError }),
}));

let boot: typeof BootModule;
let views: typeof RegistryModule;
let theme: typeof ThemeModule;
let extensions: typeof ExtensionRegistry;
let skins: typeof SkinsModule;

const Search = () => null;

const configure = (overrides: Partial<ThemeBootstrap>, rootAdmin = true) => {
    Object.assign(window, {
        SiteConfiguration: { theme: overrides },
        PterodactylUser: { uuid: 'u', username: 'admin', email: 'a@example.test', root_admin: rootAdmin },
    });
};

const chain = [
    { id: 'parent', version: '1.0.0', entry: '/parent.js' },
    { id: 'aurora', version: '1.0.0', entry: '/aurora.js' },
];

beforeEach(async () => {
    vi.resetModules();
    [boot, views, theme, extensions, skins] = await Promise.all([
        import('./boot'),
        import('./registry'),
        import('./theme'),
        import('@/extensions/registry'),
        import('@/themes/skins'),
    ]);
    vi.spyOn(console, 'error').mockImplementation(() => {});
});

afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.restoreAllMocks();
    toastError.mockClear();
    Object.assign(window, { SiteConfiguration: undefined, PterodactylUser: undefined });
});

const layerIds = (name: string) => views.getViewChain(name).map((layer) => layer.id);

describe('bootTheme', () => {
    it('registers classic, then the theme chain parent first', async () => {
        configure({ id: 'aurora', chain });
        const importer = vi.fn(async (url: string) => ({
            default: theme.defineTheme({ views: { 'app.search': Search }, icons: { layers: Search } }),
            url,
        }));

        await boot.bootTheme(importer);

        expect(importer.mock.calls.map(([url]) => url)).toEqual(['/parent.js', '/aurora.js']);
        expect(layerIds('app.search')).toEqual(['classic', 'parent', 'aurora']);
        expect(layerIds('layout.app')).toEqual(['classic']);
        expect(theme.getThemeIcon('layers')).toBe(Search);
    });

    it('stacks the skins of the chain over classic and drops them when a later boot fails', async () => {
        const badge = () => skins.getSkin('Badge', { tone: 'neutral', size: 'md', variant: 'solid' }).classes('root');
        const code = () => skins.getSkin('Code', { dark: false }).classes('root');

        configure({ id: 'aurora', chain });
        await boot.bootTheme(async (url) => ({
            default: theme.defineTheme({
                ui:
                    url === '/parent.js'
                        ? { Badge: { root: 'parent' }, Code: { root: 'parent' } }
                        : { Badge: { root: 'aurora' } },
            }),
        }));

        expect([badge(), code()]).toEqual(['aurora', 'parent']);

        await boot.bootTheme(async () => ({ default: { ui: 'not a skin map' } }));

        expect(badge()).toBe('rounded-sm text-xs px-2 py-1 bg-popover text-foreground');
        expect(extensions.getExtensionStates()[0]).toMatchObject({ id: 'aurora', status: 'failed' });
    });

    it('stays on classic without importing anything in safe mode', async () => {
        configure({ id: 'aurora', chain, safeMode: true });
        const importer = vi.fn();

        await boot.bootTheme(importer);

        expect(importer).not.toHaveBeenCalled();
        expect(layerIds('app.search')).toEqual(['classic']);
    });

    it('falls back to classic and tells admins when the chain takes too long', async () => {
        vi.useFakeTimers();
        configure({ id: 'aurora', chain });
        const booting = boot.bootTheme(() => new Promise(() => {}));

        await vi.advanceTimersByTimeAsync(boot.THEME_BOOT_TIMEOUT_MS);
        await booting;

        expect(layerIds('app.search')).toEqual(['classic']);
        expect(extensions.getExtensionStates()).toEqual([
            { id: 'aurora', status: 'failed', error: expect.stringContaining('exceeded 2500ms') },
        ]);
        render(<boot.ThemeFailureNotice />);
        expect(toastError).toHaveBeenCalledWith(
            expect.stringContaining('"aurora" theme could not load'),
            expect.objectContaining({ closeButton: true })
        );
    });

    it('registers nothing of a chain when one entry is invalid, and tells only admins', async () => {
        configure({ id: 'aurora', chain }, false);

        await boot.bootTheme(async (url) => ({
            default: url === '/aurora.js' ? { views: { 'app.search': 'not a component' } } : { views: {} },
        }));

        expect(layerIds('app.search')).toEqual(['classic']);
        expect(extensions.getExtensionStates()[0]).toMatchObject({ id: 'aurora', status: 'failed' });
        render(<boot.ThemeFailureNotice />);
        expect(toastError).not.toHaveBeenCalled();
    });
});

describe('useTheme', () => {
    it('stores the scheme preference and swaps the html class', async () => {
        configure({ scheme: 'dark', schemes: ['dark', 'light'] });
        const { renderHook } = await import('@testing-library/react');
        const { result } = renderHook(() => theme.useTheme());

        expect(result.current).toMatchObject({ id: 'classic', scheme: 'dark', schemes: ['dark', 'light'] });
        act(() => result.current.setScheme('light'));

        expect(result.current.scheme).toBe('light');
        expect(document.documentElement).toHaveClass('light');
        expect(document.documentElement).not.toHaveClass('dark');
        expect(document.cookie).toContain('pterodactyl_scheme=light');
    });
});
