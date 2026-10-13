import { useEffect } from 'react';
import { toast } from 'sonner';
import { getBootstrapTheme, getBootstrapUser } from '@/bootstrap';
import { withImportTimeout } from '@/extensions/loader';
import { failExtensionLoad } from '@/extensions/registry';
import { clearThemeSkins, registerSkins } from '@/themes/skins';
import { registerClassicTheme } from './classic';
import { clearThemeLayers, registerViewLayer } from './registry';
import { fallBackToClassic, registerThemeIcons, type ThemeDefinition } from './theme';
import './definitions';

interface ThemeModule {
    readonly default?: ThemeDefinition;
}
type ModuleImporter = (url: string) => Promise<ThemeModule>;
const nativeImport: ModuleImporter = (url) => import(/* @vite-ignore */ url);

let failure: { id: string; message: string } | undefined;

function registerTheme(id: string, theme: ThemeDefinition = {}): void {
    registerSkins(id, theme.ui ?? {});
    registerViewLayer(id, 'theme', theme.views ?? {});
    registerThemeIcons(theme.icons ?? {});
}

/**
 * Registers classic, then imports the active theme chain in parallel and registers it parent first.
 * An import that fails or takes longer than an extension bundle may leaves this browser on classic
 * and reports it.
 */
export async function bootTheme(importModule: ModuleImporter = nativeImport): Promise<void> {
    registerClassicTheme();

    const theme = getBootstrapTheme();
    const chain = theme.safeMode ? [] : theme.chain.flatMap(({ id, entry }) => (entry ? [{ id, entry }] : []));

    if (!chain.length) {
        return;
    }

    try {
        const modules = await withImportTimeout(Promise.all(chain.map(({ entry }) => importModule(entry))));

        // A view that fails renders the layer below, so one bad entry does not cost the chain.
        for (const [index, module] of modules.entries()) {
            registerTheme(chain[index].id, module.default);
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
