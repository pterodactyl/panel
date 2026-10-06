import type { NumberInputValue } from '@/components/admin/numberInput';
import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface ServerDetailsValues {
    name: string;
    user: number;
    externalId: string;
    description: string;
    extensions: ExtensionFieldValues<'admin.server'>;
}

export interface CreateServerValues {
    name: string;
    description: string;
    ownerId: number;
    startOnCompletion: boolean;

    allocationId: number;
    allocationAdditional: number[];

    databaseLimit: number;
    allocationLimit: number;
    backupLimit: number;

    cpu: number;
    threads: string;
    memory: number;
    swap: number;
    disk: number;
    io: number;
    enableOomKiller: boolean;

    eggId: number;
    skipScripts: boolean;

    image: string;
    startup: string;

    environment: Record<string, string>;
    extensions: ExtensionFieldValues<'admin.server'>;
}

export interface CreateServerFormValues extends Omit<
    CreateServerValues,
    'databaseLimit' | 'allocationLimit' | 'backupLimit' | 'cpu' | 'memory' | 'swap' | 'disk' | 'io'
> {
    databaseLimit: NumberInputValue;
    allocationLimit: NumberInputValue;
    backupLimit: NumberInputValue;
    cpu: NumberInputValue;
    memory: NumberInputValue;
    swap: NumberInputValue;
    disk: NumberInputValue;
    io: NumberInputValue;
}
