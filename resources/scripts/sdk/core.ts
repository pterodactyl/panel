// The SDK's own helpers, which ./index re-exports. They live apart so the index only re-exports, and the
// build can point an import of one SDK value at its own module (scripts/classic-sdk-imports.mjs).
import { useCurrentUser as usePanelUser } from '@/api/account/queries';
import type { UserData as SdkUser } from '@/api/account/types';
import {
    useCurrentServer as usePanelServer,
    useCurrentServerPermissions as usePanelPermissions,
    useCurrentServerUuid as usePanelServerUuid,
} from '@/api/server/queries';
import type { Server as SdkServer } from '@/api/server/types';
import { useSiteSettings as usePanelSettings } from '@/api/settings/queries';
import type { SiteSettings as SdkSiteSettings } from '@/api/settings/types';
import type { SocketEvent } from '@/components/server/events';
import { useExtensionCallback } from '@/extensions/context';
import { cn as mergeClasses } from '@/lib/cn';
import { hasPermission } from '@/plugins/usePermissions';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import type { ConfiguredExtensionContext, ExtensionConfig, ExtensionDefinition, ServerWebsocketEvent } from './index';

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

/** Identity helper giving extension entrypoints full typing: `export default definePterodactylExtension({...})`. */
export function definePterodactylExtension(definition: ExtensionDefinition): ExtensionDefinition {
    return definition;
}

/** Like definePterodactylExtension, with `config` decoded by `parseConfig` and typed screen ids. */
export function defineConfiguredExtension<TConfig extends ExtensionConfig, TScreenId extends string = string>(
    parseConfig: (config: ExtensionConfig) => TConfig,
    definition: { setup(context: ConfiguredExtensionContext<TConfig, TScreenId>): void }
): ExtensionDefinition {
    return { setup: (context) => definition.setup({ ...context, config: parseConfig(context.config) }) };
}

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
