import { useEffect } from 'react';
import { toast } from 'sonner';
import { getBootstrapTheme, getBootstrapUser } from '@/bootstrap';
import { failExtensionLoad } from '@/extensions/registry';
import { isObject } from '@/lib/objects';
import { clearThemeSkins, registerSkins } from '@/themes/skins';
import { registerClassicTheme } from './classic';
import { opaqueViews } from './opaque';
import { clearThemeLayers, isViewComponent, registerViewLayer } from './registry';
import { fallBackToClassic, registerThemeIcons, type ThemeDefinition } from './theme';
import './account';
import './activity';
import './auth';
import './dashboard';
import './layouts';
import './pages';

export const THEME_BOOT_TIMEOUT_MS = 2500;

interface ThemeModule {
    readonly default?: unknown;
}
type ModuleImporter = (url: string) => Promise<ThemeModule>;
const nativeImport: ModuleImporter = (url) => import(/* @vite-ignore */ url);

let failure: { id: string; message: string } | undefined;

function registerTheme(id: string, theme: ThemeDefinition): void {
    registerSkins(id, theme.ui ?? {});
    registerViewLayer(id, 'theme', theme.views ?? {});
    registerThemeIcons(theme.icons ?? {});
}

/** A theme entry's default export, checked before anything of the chain is registered. */
function parseTheme({ default: theme }: ThemeModule): ThemeDefinition {
    if (!isObject(theme)) {
        throw new Error('The theme entry must export defineTheme({ ui, views, icons }) as its default.');
    }

    const views = Reflect.get(theme, 'views');
    const icons = Reflect.get(theme, 'icons');
    const ui = Reflect.get(theme, 'ui');

    if (ui !== undefined && !isObject(ui)) {
        throw new Error('Theme ui must map primitive names to skins.');
    }

    for (const map of [views, icons]) {
        if (map !== undefined && !(isObject(map) && Object.values(map).every(isViewComponent))) {
            throw new Error('Theme views and icons must be components.');
        }
    }

    return { ui, views, icons };
}

function withTimeout<T>(promise: Promise<T>): Promise<T> {
    let timer: ReturnType<typeof setTimeout> | undefined;

    return Promise.race([
        promise,
        new Promise<never>((_, reject) => {
            timer = setTimeout(
                () => reject(new Error(`theme loading exceeded ${THEME_BOOT_TIMEOUT_MS}ms`)),
                THEME_BOOT_TIMEOUT_MS
            );
        }),
    ]).finally(() => clearTimeout(timer));
}

/**
 * Registers classic, then imports the active theme chain in parallel and registers it parent first.
 * Any failure or a slow chain leaves this browser on classic and reports it.
 */
export async function bootTheme(importModule: ModuleImporter = nativeImport): Promise<void> {
    registerViewLayer('classic', 'classic', opaqueViews);
    registerClassicTheme();

    const theme = getBootstrapTheme();
    const chain = theme.safeMode ? [] : theme.chain.flatMap(({ id, entry }) => (entry ? [{ id, entry }] : []));

    if (!chain.length) {
        return;
    }

    try {
        const themes = await withTimeout(Promise.all(chain.map(({ entry }) => importModule(entry).then(parseTheme))));

        for (const [index, definition] of themes.entries()) {
            registerTheme(chain[index].id, definition);
        }
    } catch (cause) {
        clearThemeLayers();
        clearThemeSkins();
        fallBackToClassic();
        failure = { id: theme.id, message: cause instanceof Error ? cause.message : String(cause) };
        failExtensionLoad(theme.id, 'theme', cause);
    }
}

/** Tells root admins once per page load that the active theme failed and classic is showing. */
export function ThemeFailureNotice() {
    useEffect(() => {
        if (!failure || !getBootstrapUser()?.rootAdmin) {
            return;
        }

        toast.error(`The "${failure.id}" theme could not load, so this page uses the classic theme.`, {
            id: 'theme-failure',
            description: failure.message,
            duration: Infinity,
            closeButton: true,
        });
    }, []);

    return null;
}
