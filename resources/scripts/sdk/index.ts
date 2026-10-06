// Every export here is public, semver-governed "@pterodactyl/sdk" API.
import type { ComponentName, ComponentReplacement } from '@/extensions/componentTypes';
import type { ComponentType } from 'react';
import type { ExtensionTableName, ExtensionTableColumn } from '@/extensions/tableTypes';
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

export { default as Button } from '@/components/elements/Button';
export { default as Spinner } from '@/components/elements/Spinner';
export { default as Icon } from '@/components/elements/Icon';
export { default as NamedIcon, loadIconNames, type NamedIconProps } from '@/components/elements/NamedIcon';
export { default as Can } from '@/components/elements/Can';
export { default as ContentBox } from '@/components/elements/ContentBox';
export { default as PageContentBlock } from '@/components/elements/PageContentBlock';
export { default as ServerContentBlock } from '@/components/elements/ServerContentBlock';
export { default as TitledGreyBox } from '@/components/elements/TitledGreyBox';
export { default as CopyOnClick } from '@/components/elements/CopyOnClick';
export { default as ScreenBlock, ServerError, NotFound } from '@/components/elements/ScreenBlock';
export { default as Label } from '@/components/elements/Label';
export { TextInput as Input, TextArea, NumberInput } from '@/components/form/controls';
export { Form, useAppForm, useFieldContext, useFormContext, type AppForm, type SelectOption } from '@/components/form';
export { default as Select, type SelectGroup, type SelectProps, type SelectValue } from '@/components/ui/Select';
export { default as Checkbox, type CheckboxProps } from '@/components/ui/Checkbox';
export { default as Switch, type SwitchProps } from '@/components/ui/Switch';
export { Alert } from '@/components/elements/alert';
export { Dialog } from '@/components/elements/dialog';
export { toast } from 'sonner';
export * from './ui';

export { default as http, httpErrorToHuman } from '@/api/http';
export { queryClient } from '@/api/queryClient';
export * from './server';
export * from './mutations';
export * from './navigation';
export * from './localization';
export * from './progress';
export * from './theme';
export { useCurrentResource, type ExtensionResourceContext } from '@/extensions/resourceContext';
export type { SdkUser, SdkSiteSettings, SdkServer };
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
    CreateFormSlotData,
    EditFormSlotData,
    ExtensionFormResources,
    FormSlotDataMap,
    FormSlotName,
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
export type {
    ExtensionFieldValue,
    ExtensionFieldValues,
    ExtensionFormFieldMap,
    ExtensionFormName,
} from '@/extensions/formFields';

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
    components: {
        replace<TName extends ComponentName>(name: TName, replacement: ComponentReplacement<TName>): void;
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
}

export { COMPONENT_NAMES } from '@/extensions/componentTypes';
export type {
    ComponentName,
    ComponentModels,
    ComponentParts,
    ComponentPartProps,
    DefaultComponentProps,
    ReplacementProps,
    ReplacementImporter,
    ComponentReplacement,
    ServerCardModel,
    ServerCardState,
    ServerCardMetric,
    FileDetailsModel,
    FileEditorModel,
    FileManagerModel,
    FileManagerEntry,
    FileManagerPermissions,
    FileManagerActions,
} from '@/extensions/componentTypes';

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
