import { createContext, useContext } from 'react';
import type { AdminNodeResource, AdminServerResource, AdminEggResource, AdminUserResource } from '@/api/extensionTypes';

export type ExtensionResourceContext =
    | { kind: 'admin.node'; resource: AdminNodeResource }
    | { kind: 'admin.server'; resource: AdminServerResource }
    | { kind: 'admin.egg'; resource: AdminEggResource }
    | { kind: 'admin.user'; resource: AdminUserResource };

export const ExtensionResourceProvider = createContext<ExtensionResourceContext | null>(null);

export function useCurrentResource(): ExtensionResourceContext | null {
    return useContext(ExtensionResourceProvider);
}
