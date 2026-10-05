import type { ComponentType } from 'react';
import type { AdminNodeResource, AdminServerResource, AdminEggResource } from '@/api/extensionTypes';

export interface ExtensionTableRows {
    'admin.nodes': AdminNodeResource;
    'admin.servers': AdminServerResource;
    'admin.eggs': AdminEggResource;
}
export type ExtensionTableName = keyof ExtensionTableRows;
export interface ExtensionTableColumn<TName extends ExtensionTableName> {
    id: string;
    label: string;
    component: ComponentType<{ data: ExtensionTableRows[TName] }>;
}
export interface ExtensionTableColumnRegistration extends ExtensionTableColumn<ExtensionTableName> {
    name: ExtensionTableName;
    extensionId: string;
}
