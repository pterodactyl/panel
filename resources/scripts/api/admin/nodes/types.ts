import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface NodeValues {
    name: string;
    description: string;
    locationId: number;
    public: boolean;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    maintenanceMode: boolean;
    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;
    uploadSize: number;
    daemonListen: number;
    daemonSftp: number;
    daemonBase: string;
    extensions: ExtensionFieldValues<'admin.node'>;
}
