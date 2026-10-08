import type { AdminNode, NodeValues } from '@/api/admin/nodes/queries';
import { type NumberInputValue, requiredNumber, submittedNumber } from '@/components/admin/numberInput';

export interface NodeFormValues extends Omit<
    NodeValues,
    'memory' | 'memoryOverallocate' | 'disk' | 'diskOverallocate' | 'uploadSize' | 'daemonListen' | 'daemonSftp'
> {
    memory: NumberInputValue;
    memoryOverallocate: NumberInputValue;
    disk: NumberInputValue;
    diskOverallocate: NumberInputValue;
    uploadSize: NumberInputValue;
    daemonListen: NumberInputValue;
    daemonSftp: NumberInputValue;
}

export const newNodeFormValues = (): NodeFormValues => ({
    name: '',
    description: '',
    locationId: 0,
    public: true,
    fqdn: '',
    scheme: 'https',
    behindProxy: false,
    maintenanceMode: false,
    memory: 1024,
    memoryOverallocate: 0,
    disk: 10240,
    diskOverallocate: 0,
    uploadSize: 100,
    daemonListen: 8080,
    daemonSftp: 2022,
    daemonBase: '/var/lib/pterodactyl/volumes',
});

export const nodeFormValues = (node: AdminNode): NodeFormValues => ({
    name: node.attributes.name,
    description: node.attributes.description ?? '',
    locationId: node.attributes.location_id,
    public: node.attributes.public,
    fqdn: node.attributes.fqdn,
    scheme: node.attributes.scheme,
    behindProxy: node.attributes.behind_proxy,
    maintenanceMode: node.attributes.maintenance_mode,
    memory: node.attributes.memory,
    memoryOverallocate: node.attributes.memory_overallocate,
    disk: node.attributes.disk,
    diskOverallocate: node.attributes.disk_overallocate,
    uploadSize: node.attributes.upload_size,
    daemonListen: node.attributes.daemon_listen,
    daemonSftp: node.attributes.daemon_sftp,
    daemonBase: node.attributes.daemon_base,
});

export const nodeValuesFromForm = ({
    memory,
    memoryOverallocate,
    disk,
    diskOverallocate,
    uploadSize,
    daemonListen,
    daemonSftp,
    ...values
}: NodeFormValues): NodeValues => ({
    ...values,
    memory: submittedNumber(memory),
    memoryOverallocate: submittedNumber(memoryOverallocate),
    disk: submittedNumber(disk),
    diskOverallocate: submittedNumber(diskOverallocate),
    uploadSize: submittedNumber(uploadSize),
    daemonListen: submittedNumber(daemonListen),
    daemonSftp: submittedNumber(daemonSftp),
});

export const validateNodeName = (value: string): string | undefined => {
    if (value.length < 1) {
        return 'A name must be provided.';
    }

    if (value.length > 100) {
        return 'A name must not exceed 100 characters.';
    }

    return undefined;
};

export const nodeNumberValidators = {
    memory: {
        onChange: requiredNumber('A memory limit must be provided.', (value) =>
            value >= 1 ? undefined : 'A memory limit must be provided.'
        ),
    },
    memoryOverallocate: { onChange: requiredNumber('A memory overallocation must be provided.') },
    disk: {
        onChange: requiredNumber('A disk limit must be provided.', (value) =>
            value >= 1 ? undefined : 'A disk limit must be provided.'
        ),
    },
    diskOverallocate: { onChange: requiredNumber('A disk overallocation must be provided.') },
    uploadSize: { onChange: requiredNumber('An upload size limit must be provided.') },
    daemonListen: { onChange: requiredNumber('A daemon port must be provided.') },
    daemonSftp: { onChange: requiredNumber('A daemon SFTP port must be provided.') },
};
