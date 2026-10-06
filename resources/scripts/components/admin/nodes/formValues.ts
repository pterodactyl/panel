import type { NodeValues } from '@/api/admin/nodes/types';
import type { NumberInputValue } from '@/components/admin/numberInput';

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

export interface NodeSettingsValues extends NodeFormValues {
    resetSecret: boolean;
}
