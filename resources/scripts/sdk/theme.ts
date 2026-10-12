// "@pterodactyl/sdk/theme": what a theme needs on its critical path, without the full SDK closure.
import * as tokens from '@/lib/theme';

export {
    defineTheme,
    useTheme,
    useThemeSettings,
    type ThemeDefinition,
    type ThemeIcon,
    type ThemeIcons,
    type ThemeScheme,
    type ThemeSettings,
    type ThemeSettingValue,
    type ThemeState,
} from '@/views/theme';
export {
    skin,
    useSkin,
    UI_PRIMITIVES,
    type CompleteSkin,
    type CompleteSkins,
    type NoState,
    type PrimitiveMeta,
    type PrimitiveName,
    type PrimitiveProps,
    type PrimitiveState,
    type ReplaceableName,
    type ResolvedSkin,
    type Skin,
    type SkinBinding,
    type SkinClassName,
    type SkinCompoundVariant,
    type SkinDefinition,
    type SkinExtension,
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
export { default as Slot } from '@/extensions/Slot';
export type {
    DefaultViewProps,
    ExtensionViewOptions,
    ExtensionViewProps,
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
