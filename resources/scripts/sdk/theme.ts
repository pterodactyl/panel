// "@pterodactyl/sdk/theme": what a theme needs on its critical path, without the full SDK closure.
import { cn as mergeClasses } from '@/lib/cn';
import * as tokens from '@/lib/theme';

export {
    defineTheme,
    useTheme,
    useThemeSettings,
    type ThemeDefinition,
    type ThemeIcon,
    type ThemeIcons,
    type ThemeScheme,
    type ThemeSchemePreference,
    type ThemeSettings,
    type ThemeSettingValue,
    type ThemeState,
} from '@/views/theme';
export {
    skin,
    useSkin,
    UI_PRIMITIVE_PARTS,
    type CompleteSkin,
    type CompleteSkins,
    type NoState,
    type PrimitiveName,
    type PrimitiveProps,
    type PrimitiveState,
    type ReplaceableName,
    type Skin,
    type SkinBelow,
    type SkinBinding,
    type SkinClassName,
    type SkinCompoundVariant,
    type SkinPart,
    type SkinPartProps,
    type SkinRecipe,
    type SkinRecipeConfig,
    type SkinStateMap,
    type SkinStateValue,
    type ThemeSkins,
    type UiPrimitives,
    type UiTone,
} from '@/themes/skins';
export { View, ViewFixtureProvider, type ViewElementProps, type ViewFixtureSelection } from '@/views/View';
export { lazyView } from '@/views/lazy';
export { Main } from '@/views/a11y';
export { usePageTitle } from '@/views/pageTitle';
export {
    Navigation,
    NavigationLink,
    useActiveNavItem,
    useNavigation,
    type NavArea,
    type NavigationLinkProps,
    type NavigationModel,
    type NavigationProps,
    type NavigationRenderer,
    type NavItem,
    type NavItemState,
} from '@/views/navigation';
export { default as Slot } from '@/extensions/Slot';
export type {
    DefaultViewProps,
    ThemeViews,
    ViewComponent,
    ViewContext,
    ViewImporter,
    ViewInput,
    ViewInputs,
    ViewModel,
    ViewModels,
    ViewName,
    ViewOverride,
    ViewPartNames,
    ViewPartProps,
    ViewParts,
    ViewProps,
} from '@/views/registry';
export type * from '@/views/models';
export type * from './models/forms';
export type * from './models/schedules';
export type * from './models/subusers';
export type * from './models/backups';
export type * from './models/settings';
export type * from './models/conflict';
export type * from './models/console';
export type * from './models/files';
export type * from './models/databases';
export type * from './models/network';
export type * from './models/startup';

/**
 * Calls `listener`, at most once a frame, after a stylesheet or an `<html>` attribute changes.
 * It is a hint, not a diff: compare the values you read. Returns the unsubscribe function.
 */
export function onThemeChange(listener: () => void): () => void {
    return tokens.onThemeChange(listener);
}

/** Report a token change the DOM cannot reveal (CSSOM rule edits, adopted stylesheets). */
export function notifyThemeChange(): void {
    tokens.notifyThemeChange();
}

/** The current value of a token on `<html>`, e.g. `readThemeToken('--chart-1')`; '' when unset. */
export function readThemeToken(name: `--${string}`): string {
    return tokens.readThemeToken(name);
}

/** Joins class names; a later Tailwind utility replaces an earlier one of the same kind: `cn('px-3', 'px-4')` is `px-4`. */
export function cn(...classes: (string | false | null | undefined)[]): string {
    return mergeClasses(...classes);
}
