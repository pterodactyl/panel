import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import {
    type AdminServer,
    updateAdminServerBuildInput,
    useAdminServerBuildAllocations,
    useAdminServerUnassignedAllocations,
    useUpdateAdminServerBuild,
} from '@/api/admin/servers/queries';
import {
    allocationLabel,
    serverBuildBodyFromFormValues,
    type ServerBuildValues,
} from '@/components/admin/servers/helpers';
import { type NumberInputValue, requiredNumber, submittedNumber } from '@/components/admin/numberInput';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerError } from '@/components/elements/ScreenBlock';

interface Props {
    server: AdminServer;
}

interface Values {
    cpu: NumberInputValue;
    threads: string;
    memory: NumberInputValue;
    swap: NumberInputValue;
    disk: NumberInputValue;
    io: NumberInputValue;
    oomDisabled: string;
    databaseLimit: NumberInputValue;
    allocationLimit: NumberInputValue;
    backupLimit: NumberInputValue;
    allocationId: number;
    addAllocations: number[];
    removeAllocations: number[];
}

const serverToValues = (server: AdminServer): Values => ({
    cpu: server.attributes.limits.cpu,
    threads: server.attributes.limits.threads ?? '',
    memory: server.attributes.limits.memory,
    swap: server.attributes.limits.swap,
    disk: server.attributes.limits.disk,
    io: server.attributes.limits.io,
    oomDisabled: server.attributes.limits.oom_disabled ? 'true' : 'false',
    databaseLimit: server.attributes.feature_limits.databases ?? 0,
    allocationLimit: server.attributes.feature_limits.allocations ?? 0,
    backupLimit: server.attributes.feature_limits.backups,
    allocationId: server.attributes.allocation,
    addAllocations: [],
    removeAllocations: [],
});

const validateCpu = requiredNumber('A CPU limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid CPU limit.'
);
const validateMemory = requiredNumber('A memory limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid memory limit.'
);
const validateSwap = requiredNumber('A swap limit must be provided.', (value) =>
    value >= -1 ? undefined : 'Invalid swap limit.'
);
const validateDisk = requiredNumber('A disk limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid disk limit.'
);
const validateIo = requiredNumber('An IO proportion must be provided.', (value): string | undefined => {
    if (value < 10) {
        return 'The IO proportion must be at least 10.';
    }

    if (value > 1000) {
        return 'The IO proportion must not exceed 1000.';
    }

    return undefined;
});
const validateDatabaseLimit = requiredNumber('A database limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid database limit.'
);
const validateAllocationLimit = requiredNumber('An allocation limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid allocation limit.'
);
const validateBackupLimit = requiredNumber('A backup limit must be provided.', (value) =>
    value >= 0 ? undefined : 'Invalid backup limit.'
);

function ServerBuildForm({ server }: Props) {
    const { attributes } = server;
    const updateServerBuild = useUpdateAdminServerBuild();

    const { data: assignedResponse } = useAdminServerBuildAllocations(attributes.id, attributes.node);
    const assigned = assignedResponse?.data ?? [];

    const { data: unassignedResponse } = useAdminServerUnassignedAllocations(attributes.node);
    const unassigned = unassignedResponse?.data ?? [];

    const form = useAppForm({
        defaultValues: serverToValues(server),
        onSubmit: async ({ value }) => {
            const payload: ServerBuildValues = {
                allocationId: Number(value.allocationId),
                addAllocations: value.addAllocations.map(Number),
                removeAllocations: value.removeAllocations.map(Number),
                oomDisabled: value.oomDisabled === 'true',
                limits: {
                    cpu: submittedNumber(value.cpu),
                    threads: value.threads,
                    memory: submittedNumber(value.memory),
                    swap: submittedNumber(value.swap),
                    disk: submittedNumber(value.disk),
                    io: submittedNumber(value.io),
                },
                featureLimits: {
                    databases: submittedNumber(value.databaseLimit),
                    allocations: submittedNumber(value.allocationLimit),
                    backups: submittedNumber(value.backupLimit),
                },
            };

            try {
                await updateServerBuild.mutateAsync(
                    updateAdminServerBuildInput(attributes.id, serverBuildBodyFromFormValues(payload))
                );
                form.reset({ ...value, addAllocations: [], removeAllocations: [] });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const defaultAllocationId = useStore(form.store, (state) => state.values.allocationId);

    const assignedOptions = assigned.map((allocation) => ({
        value: allocation.attributes.id,
        label: allocationLabel(allocation),
    }));

    const removableOptions = assignedOptions.filter((option) => option.value !== defaultAllocationId);

    return (
        <Form form={form}>
            <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
                <TitledGreyBox title='Resource Management'>
                    <form.AppField name='cpu' validators={{ onChange: validateCpu }}>
                        {(field) => (
                            <field.NumberField
                                id='cpu'
                                label='CPU Limit (%)'
                                min={0}
                                description='Each thread on the system is 100%. Set to 0 for unlimited CPU.'
                            />
                        )}
                    </form.AppField>
                    <div className='mt-6'>
                        <form.AppField name='threads'>
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='threads'
                                    label='CPU Pinning'
                                    description={
                                        'Advanced: the specific CPU cores this process can run on (e.g. 0, 0-1,3). ' +
                                        'Leave blank to allow all cores.'
                                    }
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='memory' validators={{ onChange: validateMemory }}>
                            {(field) => (
                                <field.NumberField
                                    id='memory'
                                    label='Allocated Memory (MiB)'
                                    min={0}
                                    description='The maximum memory for this container. Set to 0 for unlimited.'
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='swap' validators={{ onChange: validateSwap }}>
                            {(field) => (
                                <field.NumberField
                                    id='swap'
                                    label='Allocated Swap (MiB)'
                                    min={-1}
                                    description='Set to 0 to disable swap, or -1 for unlimited swap.'
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='disk' validators={{ onChange: validateDisk }}>
                            {(field) => (
                                <field.NumberField
                                    id='disk'
                                    label='Disk Space Limit (MiB)'
                                    min={0}
                                    description='Set to 0 to allow unlimited disk usage.'
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='io' validators={{ onChange: validateIo }}>
                            {(field) => (
                                <field.NumberField
                                    id='io'
                                    label='Block IO Proportion'
                                    min={10}
                                    max={1000}
                                    description='Advanced: a value between 10 and 1000.'
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='oomDisabled'>
                            {(field) => (
                                <field.SelectField
                                    id='oomDisabled'
                                    label='OOM Killer'
                                    description='Enabling the OOM killer may cause server processes to exit unexpectedly.'
                                    options={[
                                        { value: 'false', label: 'Enabled' },
                                        { value: 'true', label: 'Disabled' },
                                    ]}
                                />
                            )}
                        </form.AppField>
                    </div>
                </TitledGreyBox>
                <div className='space-y-6'>
                    <TitledGreyBox title='Application Feature Limits'>
                        <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
                            <form.AppField name='databaseLimit' validators={{ onChange: validateDatabaseLimit }}>
                                {(field) => <field.NumberField id='database_limit' label='Database Limit' min={0} />}
                            </form.AppField>
                            <form.AppField name='allocationLimit' validators={{ onChange: validateAllocationLimit }}>
                                {(field) => (
                                    <field.NumberField id='allocation_limit' label='Allocation Limit' min={0} />
                                )}
                            </form.AppField>
                            <form.AppField name='backupLimit' validators={{ onChange: validateBackupLimit }}>
                                {(field) => <field.NumberField id='backup_limit' label='Backup Limit' min={0} />}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                    <TitledGreyBox title='Allocation Management'>
                        <form.AppField name='allocationId'>
                            {(field) => (
                                <field.SelectField
                                    id='allocationId'
                                    label='Game Port'
                                    description='The default connection address for this server.'
                                    options={assignedOptions}
                                    onChange={(value) =>
                                        form.setFieldValue('removeAllocations', (current) =>
                                            current.filter((id) => id !== Number(value))
                                        )
                                    }
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6'>
                            <form.AppField name='addAllocations'>
                                {(field) => (
                                    <field.MultiSelectField
                                        id='addAllocations'
                                        label='Assign Additional Ports'
                                        description='Unassigned ports on this node that will be assigned to the server.'
                                        options={unassigned.map((allocation) => ({
                                            value: allocation.attributes.id,
                                            label: allocationLabel(allocation),
                                        }))}
                                    />
                                )}
                            </form.AppField>
                        </div>
                        <div className='mt-6'>
                            <form.AppField name='removeAllocations'>
                                {(field) => (
                                    <field.MultiSelectField
                                        id='removeAllocations'
                                        label='Remove Additional Ports'
                                        description='Select the ports to remove from this server.'
                                        options={removableOptions}
                                    />
                                )}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                </div>
            </div>
            <div className='flex justify-end mt-6'>
                <form.AppForm>
                    <form.SubmitButton>Update Build Configuration</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

export default function ServerBuildTab() {
    const { server } = useServerDetail();

    if (server.attributes.container.installed !== 1) {
        return <ServerError message='Access to this resource is not allowed due to the current installation state.' />;
    }

    return <ServerBuildForm key={`${server.attributes.id}:${server.attributes.updated_at}`} server={server} />;
}
