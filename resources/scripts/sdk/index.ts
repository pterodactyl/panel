// Every export here is public, semver-governed "@pterodactyl/sdk" API.
import type { ComponentType } from 'react';
import type { ExtensionViewOptions, ViewName, ViewOverride } from '@/views/registry';
import type { ExtensionTableName, ExtensionTableColumn } from '@/extensions/tableTypes';
import type { ExtensionFieldValues, ExtensionFormName, FormExtension } from '@/extensions/formTypes';
import {
    SLOT_NAMES,
    type SlotComponentProps,
    type SlotData,
    type ScreenImporter,
    type ScreenComponentProps,
    type ScreenOptions,
    type ExtensionScreenDefinition,
    type RouteSlotData,
    type SlotName,
    type SubuserPermissionsSlotData,
} from '@/extensions/registry';
import {
    useCurrentServer as usePanelServer,
    useCurrentServerPermissions as usePanelPermissions,
    useCurrentServerUuid as usePanelServerUuid,
} from '@/api/server/queries';
import { useCurrentUser as usePanelUser } from '@/api/account/queries';
import { useSiteSettings as usePanelSettings } from '@/api/settings/queries';
import type { UserData as SdkUser } from '@/api/account/types';
import type { SiteSettings as SdkSiteSettings } from '@/api/settings/types';
import type { Server as SdkServer } from '@/api/server/types';
import { useExtensionCallback } from '@/extensions/context';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { SocketEvent } from '@/components/server/events';
import { hasPermission } from '@/plugins/usePermissions';
import { cn as mergeClasses } from '@/lib/cn';

export {
    default as Button,
    ButtonStyle,
    LinkButton,
    RouterLinkButton,
    type ButtonColor,
    type ButtonProps,
    type ButtonSize,
} from '@/components/elements/Button';
export { default as Spinner, type SpinnerProps, type SpinnerSize } from '@/components/elements/Spinner';
export { default as Icon } from '@/components/elements/Icon';
export { default as NamedIcon, loadIconNames, type NamedIconProps } from '@/components/elements/NamedIcon';
export { default as Can } from '@/components/elements/Can';
export { default as Card, type CardProps } from '@/components/elements/Card';
export { default as ListRow, type ListRowProps } from '@/components/elements/ListRow';
export { default as Badge, type BadgeProps, type BadgeSize, type BadgeVariant } from '@/components/elements/Badge';
export {
    default as ProgressBar,
    type ProgressBarProps,
    type ProgressBarVariant,
} from '@/components/elements/ProgressBar';
export { default as Code, type CodeProps } from '@/components/elements/Code';
export { default as ContentBox } from '@/components/elements/ContentBox';
export { default as PageContentBlock } from '@/components/elements/PageContentBlock';
export { default as ServerContentBlock } from '@/components/elements/ServerContentBlock';
export { default as TitledGreyBox } from '@/components/elements/TitledGreyBox';
export { default as CopyOnClick, type CopyOnClickProps } from '@/components/elements/CopyOnClick';
export { default as ScreenBlock, ServerError, NotFound, screenImages } from '@/components/elements/ScreenBlock';
export { default as PaginationFooter } from '@/components/elements/table/PaginationFooter';
export { default as Translate, type TranslationValue, type TranslationValues } from '@/components/elements/Translate';
export { default as Label, type LabelProps } from '@/components/elements/Label';
export { default as PageHeading, type PageHeadingProps } from '@/components/elements/PageHeading';
export {
    NewButton,
    NewLinkButton,
    type NewButtonProps,
    type NewLinkButtonProps,
} from '@/components/elements/NewButton';
export { default as SpinnerOverlay, type SpinnerOverlayProps } from '@/components/elements/SpinnerOverlay';
export { default as ErrorBoundary } from '@/components/elements/ErrorBoundary';
export { default as Portal } from '@/components/elements/Portal';
export { default as InputSpinner } from '@/components/elements/InputSpinner';
export {
    TextInput as Input,
    TextArea,
    NumberInput,
    FileInput,
    type TextInputProps as InputProps,
    type TextAreaProps,
    type NumberInputProps,
    type FileInputProps,
} from '@/components/form/controls';
export {
    default as SubNavigation,
    type SubNavigationItemState,
    type SubNavigationProps,
} from '@/components/elements/SubNavigation';
export { default as SubNavigationLayout } from '@/components/elements/SubNavigationLayout';
export { default as NavigationLabel, type NavigationLabelProps } from '@/extensions/NavigationLabel';
export { Form, useAppForm, useFieldContext, useFormContext, type AppForm, type SelectOption } from '@/components/form';
export { default as Select, type SelectGroup, type SelectProps, type SelectValue } from '@/components/ui/Select';
export { default as Checkbox, type CheckboxProps } from '@/components/ui/Checkbox';
export { default as Switch, type SwitchProps } from '@/components/ui/Switch';
export { Alert, type AlertProps, type AlertType } from '@/components/elements/alert';
export {
    Dialog,
    useDialogState,
    type DialogIconProps,
    type RenderDialogProps as DialogProps,
} from '@/components/elements/dialog';
export { toast } from 'sonner';
export * from './ui';

export { default as http, httpErrorToHuman } from '@/api/http';
export { queryClient } from '@/api/queryClient';
export * from './server';
export * from './mutations';
export * from './navigation';
export * from './localization';
export * from './progress';
export { onThemeChange, notifyThemeChange, readThemeToken } from './theme';
export { bytesToString, ip, mbToBytes } from '@/lib/formatters';
export { formatDate, fromNow } from '@/lib/dates';
export { useDebouncedCallback, type DebouncedCallback } from '@/plugins/useDebouncedCallback';
export { useCurrentResource, type ExtensionResourceContext } from '@/extensions/resourceContext';
export type { SdkUser, SdkSiteSettings, SdkServer };
/** Joins class names; a later Tailwind utility replaces an earlier one of the same kind, prefixes included. */
export function cn(...classes: (string | false | null | undefined)[]): string {
    return mergeClasses(...classes);
}

export function useCurrentUser(): SdkUser {
    return usePanelUser();
}

export function useSiteSettings(): SdkSiteSettings {
    return usePanelSettings();
}

export function useCurrentServer<TData = SdkServer>(select?: (server: SdkServer) => TData): TData | undefined {
    return usePanelServer(select);
}

export function useCurrentServerPermissions(): string[] {
    return usePanelPermissions();
}

export { usePermissions } from '@/plugins/usePermissions';
export { useExtensionCallback as useExtensionAction } from '@/extensions/context';

export { SLOT_NAMES };
export type { ExtensionScreenDefinition, RouteSlotData, ScreenComponentProps, SlotName, SubuserPermissionsSlotData };
export type {
    AdminUserFormSlotData,
    FileManagerSlotData,
    FileRowSlotData,
    StartupFormSlotData,
    ScreenParent,
    ScreenBadgeValue,
    ScreenCondition,
    ScreenContext,
    ScreenMatcher,
    ScreenOptions,
} from '@/extensions/registry';
export type { ExtensionTableColumn, ExtensionTableName, ExtensionTableRows } from '@/extensions/tableTypes';
export { EXTENSION_FORM_NAMES } from '@/extensions/formTypes';
export type {
    ExtensionField,
    ExtensionFieldValue,
    ExtensionFieldValues,
    ExtensionFormName,
    ExtensionFormResources,
    FormExtension,
    FormExtensionProps,
} from '@/extensions/formTypes';

export type { SlotComponentProps, SlotData } from '@/extensions/registry';

export interface ExtensionMeta {
    id: string;
    version?: string;
}

export type ExtensionConfigValue =
    | string
    | number
    | boolean
    | null
    | readonly ExtensionConfigValue[]
    | { readonly [key: string]: ExtensionConfigValue };

export interface ExtensionConfig {
    readonly [key: string]: ExtensionConfigValue;
}

export interface ExtensionSetupContext {
    meta: ExtensionMeta;
    /** Per-install extension settings. */
    config: ExtensionConfig;
    views: {
        /** Stacks a layer on a view declared in the manifest's `ui.views`; its `Default` renders the layer below. */
        override<TName extends ViewName>(name: TName, view: ViewOverride<TName>): void;
        /** Adds a view of the extension's own, which themes and other extensions can restyle or place. */
        define<TModel>(name: string, view: ExtensionViewOptions<TModel>): void;
    };
    slots: {
        /** Mount a component at a named anchor point; see SlotName for the catalog. */
        register<TName extends SlotName>(
            name: TName,
            component: ComponentType<SlotComponentProps<SlotData<TName>>>
        ): void;
    };
    screens: {
        /**
         * Attaches an implementation to a screen declared in extension.json `ui.screens`.
         * `options` can add a live nav badge and the `when.runtime` visibility predicate.
         */
        register(id: string, component: ScreenImporter, options?: ScreenOptions): void;
    };
    columns: {
        register<TName extends ExtensionTableName>(name: TName, column: ExtensionTableColumn<TName>): void;
    };
    forms: {
        /**
         * Draws the extension's fields inside an admin form, when creating and when editing.
         * They are the fields its PHP `Fields` class declares for the form's model with
         * `registerFields()`; the panel saves them with the form.
         */
        extend<TName extends ExtensionFormName, TValues extends ExtensionFieldValues>(
            form: TName,
            component: FormExtension<TValues, TName>
        ): void;
    };
}

export type {
    DefaultViewProps,
    ExtensionViewOptions,
    ExtensionViewProps,
    ViewComponent,
    ViewContext,
    ViewImporter,
    ViewModel,
    ViewName,
    ViewOverride,
    ViewPartProps,
    ViewParts,
    ViewProps,
} from '@/views/registry';
export type * from '@/views/models';
export type * from './models/forms';
export type * from './models/auth';
export type * from './models/account';
export type * from './models/activity';
export * from './models/schedules';
export * from './models/subusers';
export * from './models/backups';
export * from './models/settings';
export * from './models/conflict';
export * from './models/console';
export * from './models/files';
export * from './models/databases';
export * from './models/network';
export * from './models/startup';

export interface ExtensionDefinition {
    setup(context: ExtensionSetupContext): void;
}

/** Identity helper giving extension entrypoints full typing: `export default definePterodactylExtension({...})`. */
export function definePterodactylExtension(definition: ExtensionDefinition): ExtensionDefinition {
    return definition;
}

export type ConfiguredExtensionContext<TConfig extends ExtensionConfig, TScreenId extends string> = Omit<
    ExtensionSetupContext,
    'config' | 'screens'
> & {
    config: TConfig;
    screens: { register(id: TScreenId, component: ScreenImporter, options?: ScreenOptions): void };
};

/** Like definePterodactylExtension, with `config` decoded by `parseConfig` and typed screen ids. */
export function defineConfiguredExtension<TConfig extends ExtensionConfig, TScreenId extends string = string>(
    parseConfig: (config: ExtensionConfig) => TConfig,
    definition: { setup(context: ConfiguredExtensionContext<TConfig, TScreenId>): void }
): ExtensionDefinition {
    return { setup: (context) => definition.setup({ ...context, config: parseConfig(context.config) }) };
}

export { SocketEvent };
export type ServerWebsocketEvent = SocketEvent | `${SocketEvent}`;

/**
 * Subscribes to a server websocket event while mounted under /server/$id.
 * Only call from server screens, server slots, or components rendered under them.
 */
export function useServerWebsocketEvent(
    event: ServerWebsocketEvent,
    callback: (data: string) => void | Promise<void>
): void {
    useWebsocketEvent(event as SocketEvent, useExtensionCallback(event, callback));
}

export function useCurrentServerUuid(): string | undefined {
    return usePanelServerUuid();
}

export function useCurrentServerRequired(message = 'This extension screen requires a server context.'): SdkServer {
    const server = useCurrentServer();

    if (!server) {
        throw new Error(message);
    }

    return server;
}

export function useServerPermission(permission: string | string[], matchAny = false): boolean {
    const permissions = useCurrentServerPermissions();
    const required = Array.isArray(permission) ? permission : [permission];
    const matches = required.map((item) => hasPermission(permissions, item));

    return matchAny ? matches.some(Boolean) : matches.every(Boolean);
}

export type { UserValues as AdminUserFormValues } from '@/api/admin/users/types';
