import { useMemo } from 'react';
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
import { useTranslation, type Translation } from '@/sdk/localization';

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

const buildValidators = (t: Translation['t']) => ({
    cpu: requiredNumber(t('A CPU limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid CPU limit.')
    ),
    memory: requiredNumber(t('A memory limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid memory limit.')
    ),
    swap: requiredNumber(t('A swap limit must be provided.'), (value) =>
        value >= -1 ? undefined : t('Invalid swap limit.')
    ),
    disk: requiredNumber(t('A disk limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid disk limit.')
    ),
    io: requiredNumber(t('An IO proportion must be provided.'), (value): string | undefined => {
        if (value < 10) {
            return t('The IO proportion must be at least 10.');
        }

        if (value > 1000) {
            return t('The IO proportion must not exceed 1000.');
        }

        return undefined;
    }),
    databaseLimit: requiredNumber(t('A database limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid database limit.')
    ),
    allocationLimit: requiredNumber(t('An allocation limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid allocation limit.')
    ),
    backupLimit: requiredNumber(t('A backup limit must be provided.'), (value) =>
        value >= 0 ? undefined : t('Invalid backup limit.')
    ),
});

function ServerBuildForm({ server }: Props) {
    const { t } = useTranslation();
    const validators = useMemo(() => buildValidators(t), [t]);
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
                <TitledGreyBox title={t('Resource Management')}>
                    <form.AppField name='cpu' validators={{ onChange: validators.cpu }}>
                        {(field) => (
                            <field.NumberField
                                id='cpu'
                                label={t('CPU Limit (%)')}
                                min={0}
                                description={t('Each thread on the system is 100%. Set to 0 for unlimited CPU.')}
                            />
                        )}
                    </form.AppField>
                    <div className='mt-6'>
                        <form.AppField name='threads'>
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='threads'
                                    label={t('CPU Pinning')}
                                    description={t(
                                        'Advanced: the specific CPU cores this process can run on (e.g. 0, 0-1,3). Leave blank to allow all cores.'
                                    )}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='memory' validators={{ onChange: validators.memory }}>
                            {(field) => (
                                <field.NumberField
                                    id='memory'
                                    label={t('Allocated Memory (MiB)')}
                                    min={0}
                                    description={t('The maximum memory for this container. Set to 0 for unlimited.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='swap' validators={{ onChange: validators.swap }}>
                            {(field) => (
                                <field.NumberField
                                    id='swap'
                                    label={t('Allocated Swap (MiB)')}
                                    min={-1}
                                    description={t('Set to 0 to disable swap, or -1 for unlimited swap.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='disk' validators={{ onChange: validators.disk }}>
                            {(field) => (
                                <field.NumberField
                                    id='disk'
                                    label={t('Disk Space Limit (MiB)')}
                                    min={0}
                                    description={t('Set to 0 to allow unlimited disk usage.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='io' validators={{ onChange: validators.io }}>
                            {(field) => (
                                <field.NumberField
                                    id='io'
                                    label={t('Block IO Proportion')}
                                    min={10}
                                    max={1000}
                                    description={t('Advanced: a value between 10 and 1000.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='oomDisabled'>
                            {(field) => (
                                <field.SelectField
                                    id='oomDisabled'
                                    label={t('OOM Killer')}
                                    description={t(
                                        'Enabling the OOM killer may cause server processes to exit unexpectedly.'
                                    )}
                                    options={[
                                        { value: 'false', label: t('Enabled') },
                                        { value: 'true', label: t('Disabled') },
                                    ]}
                                />
                            )}
                        </form.AppField>
                    </div>
                </TitledGreyBox>
                <div className='space-y-6'>
                    <TitledGreyBox title={t('Application Feature Limits')}>
                        <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
                            <form.AppField name='databaseLimit' validators={{ onChange: validators.databaseLimit }}>
                                {(field) => (
                                    <field.NumberField id='database_limit' label={t('Database Limit')} min={0} />
                                )}
                            </form.AppField>
                            <form.AppField name='allocationLimit' validators={{ onChange: validators.allocationLimit }}>
                                {(field) => (
                                    <field.NumberField id='allocation_limit' label={t('Allocation Limit')} min={0} />
                                )}
                            </form.AppField>
                            <form.AppField name='backupLimit' validators={{ onChange: validators.backupLimit }}>
                                {(field) => <field.NumberField id='backup_limit' label={t('Backup Limit')} min={0} />}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                    <TitledGreyBox title={t('Allocation Management')}>
                        <form.AppField name='allocationId'>
                            {(field) => (
                                <field.SelectField
                                    id='allocationId'
                                    label={t('Game Port')}
                                    description={t('The default connection address for this server.')}
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
                                        label={t('Assign Additional Ports')}
                                        description={t(
                                            'Unassigned ports on this node that will be assigned to the server.'
                                        )}
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
                                        label={t('Remove Additional Ports')}
                                        description={t('Select the ports to remove from this server.')}
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
                    <form.SubmitButton>{t('Update Build Configuration')}</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

export default function ServerBuildTab() {
    const { t } = useTranslation();
    const { server } = useServerDetail();

    if (server.attributes.container.installed !== 1) {
        return (
            <ServerError message={t('Access to this resource is not allowed due to the current installation state.')} />
        );
    }

    return <ServerBuildForm key={`${server.attributes.id}:${server.attributes.updated_at}`} server={server} />;
}
