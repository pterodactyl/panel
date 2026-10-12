import { useSyncExternalStore, type ComponentType } from 'react';
import type { LucideProps } from 'lucide-react';
import { getBootstrapTheme, type ThemeBootstrap, type ThemeScheme, type ThemeSettingValue } from '@/bootstrap';
import type { ThemeSkins } from '@/themes/skins';
import type { ThemeViews } from './registry';

export type { ThemeScheme, ThemeSettingValue };
export type ThemeIcon = ComponentType<LucideProps>;
export type ThemeIcons = Readonly<Record<string, ThemeIcon>>;

export interface ThemeDefinition {
    readonly ui?: ThemeSkins;
    readonly views?: ThemeViews;
    /** Icons by lucide name; `<NamedIcon>` resolves these before the panel's own set. */
    readonly icons?: ThemeIcons;
}

/** Identity helper typing a theme entry: `export default defineTheme({ ui, views, icons })`. */
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
    readonly scheme: ThemeScheme;
    readonly schemes: readonly ThemeScheme[];
    /** Stores the preference in the `pterodactyl_scheme` cookie and swaps the `<html>` class. */
    setScheme(preference: ThemeScheme | 'system'): void;
}

const icons = new Map<string, ThemeIcon>();
const listeners = new Set<() => void>();
let fallback = false;
let scheme: ThemeScheme | undefined;

export function registerThemeIcons(map: ThemeIcons): void {
    for (const [name, icon] of Object.entries(map)) {
        icons.set(name, icon);
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

export function setThemeScheme(preference: ThemeScheme | 'system'): void {
    const { schemes } = getBootstrapTheme();
    const preferred = preference === 'system' && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    const resolved = preference === 'system' ? preferred : preference;
    const next = schemes.includes(resolved) ? resolved : (schemes[0] ?? 'dark');

    document.cookie = `pterodactyl_scheme=${preference}; path=/; max-age=31536000; samesite=lax`;
    document.documentElement.classList.remove('dark', 'light');
    document.documentElement.classList.add(next);
    scheme = next;
    for (const listener of listeners) {
        listener();
    }
}

function subscribeScheme(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}

export function useTheme(): ThemeState {
    const current = useSyncExternalStore(subscribeScheme, currentScheme);
    const theme = getBootstrapTheme();

    return {
        id: fallback ? 'classic' : theme.id,
        chain: fallback ? [] : theme.chain,
        scheme: current,
        schemes: theme.schemes,
        setScheme: setThemeScheme,
    };
}

export function useThemeSettings(): ThemeSettings {
    return getBootstrapTheme().settings;
}
