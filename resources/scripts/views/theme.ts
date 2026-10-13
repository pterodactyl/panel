import { useSyncExternalStore, type ComponentType } from 'react';
import type { LucideProps } from 'lucide-react';
import { getBootstrapTheme, type ThemeBootstrap, type ThemeScheme, type ThemeSettingValue } from '@/bootstrap';
import type { ThemeSkins } from '@/themes/skins';
import type { ExtensionSetupContext } from '@/sdk';
import { isViewComponent, type ThemeViews } from './registry';

export type { ThemeScheme, ThemeSettingValue };
/** A scheme the user chose, or `system` to follow the device. */
export type ThemeSchemePreference = ThemeScheme | 'system';
export type ThemeIcon = ComponentType<LucideProps>;
export type ThemeIcons = Readonly<Record<string, ThemeIcon>>;

export interface ThemeDefinition {
    readonly ui?: ThemeSkins;
    readonly views?: ThemeViews;
    /** Icons by lucide name; `<NamedIcon>` resolves these before the panel's own set. */
    readonly icons?: ThemeIcons;
    /**
     * What an extension's entry does in `setup`, with the same context: slots, screens, table columns
     * and form fields. It runs while the theme, or a theme extending it, is active.
     */
    setup?(context: ExtensionSetupContext): void;
}

/** Identity helper typing a theme entry: `export default defineTheme({ ui, views, icons, setup })`. */
export function defineTheme(definition: ThemeDefinition): ThemeDefinition {
    return definition;
}

/** Setting values of the active theme; `p:extension:types` adds the keys a theme declares. */
export interface ThemeSettings {
    readonly [key: string]: ThemeSettingValue;
}

export interface ThemeState {
    readonly id: string;
    readonly chain: ThemeBootstrap['chain'];
    /** The scheme in use. */
    readonly scheme: ThemeScheme;
    /** What the user chose, `system` included; a scheme switch shows this one as selected. */
    readonly preference: ThemeSchemePreference;
    readonly schemes: readonly ThemeScheme[];
    /** Stores the preference in the `pterodactyl_scheme` cookie and swaps the `<html>` class. */
    setScheme(preference: ThemeSchemePreference): void;
}

const icons = new Map<string, ThemeIcon>();
const listeners = new Set<() => void>();
let fallback = false;
let scheme: ThemeScheme | undefined;
let preference: ThemeSchemePreference | undefined;
let followingDevice = false;

/** Adds a theme's icons; an entry that is not a component is skipped, as no view boundary would catch it. */
export function registerThemeIcons(map: ThemeIcons): void {
    for (const [name, icon] of Object.entries(map)) {
        if (isViewComponent(icon)) {
            icons.set(name, icon);
        }
    }
}

export function getThemeIcon(name: string): ThemeIcon | undefined {
    return icons.get(name);
}

/** The browser shows classic after the active theme failed to boot. */
export function fallBackToClassic(): void {
    fallback = true;
    icons.clear();
}

function currentScheme(): ThemeScheme {
    return (scheme ??= getBootstrapTheme().scheme);
}

/** The stored preference; a scheme the theme does not offer was replaced by its default when the page loaded. */
function currentPreference(): ThemeSchemePreference {
    if (preference === undefined) {
        const stored = /(?:^|;\s*)pterodactyl_scheme=(\w+)/.exec(document.cookie)?.[1];
        const offered = getBootstrapTheme().schemes.find((candidate) => candidate === stored);

        preference = stored === 'system' ? 'system' : (offered ?? currentScheme());
    }

    return preference;
}

const prefersLight = () => matchMedia('(prefers-color-scheme: light)');

const deviceScheme = (): ThemeScheme => (prefersLight().matches ? 'light' : 'dark');

function applyScheme(chosen: ThemeSchemePreference): void {
    const { schemes } = getBootstrapTheme();
    const resolved = chosen === 'system' ? deviceScheme() : chosen;
    const next = schemes.includes(resolved) ? resolved : (schemes[0] ?? 'dark');

    document.documentElement.classList.remove('dark', 'light');
    document.documentElement.classList.add(next);
    scheme = next;
    for (const listener of listeners) {
        listener();
    }
}

export function setThemeScheme(chosen: ThemeSchemePreference): void {
    document.cookie = `pterodactyl_scheme=${chosen}; path=/; max-age=31536000; samesite=lax`;
    preference = chosen;
    applyScheme(chosen);
}

function subscribeScheme(listener: () => void): () => void {
    listeners.add(listener);

    // While the preference is "system", the scheme follows the device as it changes.
    if (!followingDevice) {
        followingDevice = true;
        prefersLight().addEventListener('change', () => {
            if (currentPreference() === 'system') {
                applyScheme('system');
            }
        });
    }

    return () => {
        listeners.delete(listener);
    };
}

export function useTheme(): ThemeState {
    const current = useSyncExternalStore(subscribeScheme, currentScheme);
    const chosen = useSyncExternalStore(subscribeScheme, currentPreference);
    const theme = getBootstrapTheme();

    return {
        id: fallback ? 'classic' : theme.id,
        chain: fallback ? [] : theme.chain,
        scheme: current,
        preference: chosen,
        schemes: theme.schemes,
        setScheme: setThemeScheme,
    };
}

/** Pass the `ThemeSettings` type `p:extension:types` generates for the theme to type the values. */
export function useThemeSettings<T = ThemeSettings>(): T;
export function useThemeSettings(): ThemeSettings {
    return getBootstrapTheme().settings;
}
