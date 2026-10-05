import type { AppForm } from '@/components/form';
import { type AdminLocation, useAllAdminLocations } from '@/api/admin/locations/queries';
import { type NodeFormValues, nodeNumberValidators } from '@/components/admin/nodes/nodeForm';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

interface Props {
    form: AppForm<NodeFormValues>;
    prefix?: string;
    requiresSslScheme?: boolean;
}

type LocationOption = AdminLocation;

interface FormFieldsProps {
    form: AppForm<NodeFormValues>;
    prefix: string;
}

interface IdentityFieldsProps extends FormFieldsProps {
    locations: LocationOption[];
}

interface NetworkFieldsProps extends FormFieldsProps {
    requiresSslScheme: boolean;
}

const IdentityFields = ({ form, locations, prefix }: IdentityFieldsProps) => (
    <div className={'space-y-6'}>
        <form.AppField
            name={'name'}
            validators={{
                onChange: ({ value }) =>
                    value.length < 1
                        ? 'A name must be provided.'
                        : value.length > 100
                          ? 'A name must not exceed 100 characters.'
                          : undefined,
            }}
        >
            {(field) => (
                <field.TextField
                    type={'text'}
                    id={`${prefix}name`}
                    label={'Name'}
                    description={'A short identifier used to distinguish this node from others.'}
                />
            )}
        </form.AppField>
        <form.AppField name={'description'}>
            {(field) => (
                <field.TextField
                    type={'text'}
                    id={`${prefix}description`}
                    label={'Description'}
                    description={'A longer description of this node.'}
                />
            )}
        </form.AppField>
        <form.AppField
            name={'locationId'}
            validators={{
                onChange: ({ value }) => (value >= 1 ? undefined : 'A location must be selected.'),
            }}
        >
            {(field) => (
                <field.SelectField
                    id={`${prefix}location_id`}
                    label={'Location'}
                    options={locations.map((location) => ({
                        value: location.attributes.id,
                        label: location.attributes.short,
                    }))}
                />
            )}
        </form.AppField>
    </div>
);

const NetworkFields = ({ form, prefix, requiresSslScheme }: NetworkFieldsProps) => {
    const schemeDescription = requiresSslScheme
        ? 'This Panel is using HTTPS, so browsers require node connections to use SSL.'
        : 'In most cases you should use SSL. Use HTTP only for non-SSL nodes.';

    return (
        <div className={'space-y-6'}>
            <div className={'grid grid-cols-1 lg:grid-cols-[1fr_16rem] gap-6'}>
                <form.AppField
                    name={'fqdn'}
                    validators={{
                        onChange: ({ value }) => (value.length >= 1 ? undefined : 'An FQDN must be provided.'),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type={'text'}
                            id={`${prefix}fqdn`}
                            label={'FQDN'}
                            description={'The domain name or IP address that points to this node.'}
                        />
                    )}
                </form.AppField>
                <form.AppField
                    name={'scheme'}
                    validators={{
                        onChange: ({ value }) =>
                            requiresSslScheme && value === 'http'
                                ? 'HTTP node connections cannot be used when the Panel is loaded over HTTPS.'
                                : undefined,
                    }}
                >
                    {(field) => (
                        <field.SelectField
                            id={`${prefix}scheme`}
                            label={'Communicate Over SSL'}
                            options={[
                                { value: 'https', label: 'Use SSL Connection (https)' },
                                {
                                    value: 'http',
                                    label: 'Use HTTP Connection (http)',
                                    disabled: requiresSslScheme,
                                },
                            ]}
                            description={schemeDescription}
                        />
                    )}
                </form.AppField>
            </div>
            <div className={'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-x-8 gap-y-5'}>
                <form.AppField name={'public'}>
                    {(field) => (
                        <field.SwitchField
                            label={'Node Visibility'}
                            description={'Public nodes are available for automatic server deployment.'}
                            controlPosition={'end'}
                            className={'w-full'}
                        />
                    )}
                </form.AppField>
                <form.AppField name={'behindProxy'}>
                    {(field) => (
                        <field.SwitchField
                            label={'Behind Proxy'}
                            description={'Enable if this node sits behind a proxy such as Cloudflare.'}
                            controlPosition={'end'}
                            className={'w-full'}
                        />
                    )}
                </form.AppField>
                <form.AppField name={'maintenanceMode'}>
                    {(field) => (
                        <field.SwitchField
                            label={'Maintenance Mode'}
                            description={'Servers on a node in maintenance mode cannot be accessed.'}
                            controlPosition={'end'}
                            className={'w-full sm:col-span-2 xl:col-span-1'}
                        />
                    )}
                </form.AppField>
            </div>
        </div>
    );
};

const ResourceFields = ({ form, prefix }: FormFieldsProps) => (
    <div className={'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6'}>
        <form.AppField name={'memory'} validators={nodeNumberValidators.memory}>
            {(field) => <field.NumberField id={`${prefix}memory`} label={'Memory (MiB)'} min={1} />}
        </form.AppField>
        <form.AppField name={'memoryOverallocate'} validators={nodeNumberValidators.memoryOverallocate}>
            {(field) => (
                <field.NumberField id={`${prefix}memory_overallocate`} label={'Memory Overallocate (%)'} min={-1} />
            )}
        </form.AppField>
        <form.AppField name={'disk'} validators={nodeNumberValidators.disk}>
            {(field) => <field.NumberField id={`${prefix}disk`} label={'Disk Space (MiB)'} min={1} />}
        </form.AppField>
        <form.AppField name={'diskOverallocate'} validators={nodeNumberValidators.diskOverallocate}>
            {(field) => (
                <field.NumberField id={`${prefix}disk_overallocate`} label={'Disk Overallocate (%)'} min={-1} />
            )}
        </form.AppField>
    </div>
);

const DaemonFields = ({ form, prefix }: FormFieldsProps) => (
    <div className={'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[12rem_12rem_12rem_1fr] gap-6'}>
        <form.AppField name={'daemonListen'} validators={nodeNumberValidators.daemonListen}>
            {(field) => <field.NumberField id={`${prefix}daemon_listen`} label={'Daemon Port'} min={1} max={65535} />}
        </form.AppField>
        <form.AppField name={'daemonSftp'} validators={nodeNumberValidators.daemonSftp}>
            {(field) => (
                <field.NumberField id={`${prefix}daemon_sftp`} label={'Daemon SFTP Port'} min={1} max={65535} />
            )}
        </form.AppField>
        <form.AppField name={'uploadSize'} validators={nodeNumberValidators.uploadSize}>
            {(field) => <field.NumberField id={`${prefix}upload_size`} label={'Upload Size Limit (MiB)'} min={1} />}
        </form.AppField>
        <form.AppField name={'daemonBase'}>
            {(field) => <field.TextField type={'text'} id={`${prefix}daemon_base`} label={'Daemon Base Path'} />}
        </form.AppField>
    </div>
);

export default function NodeFormFields({ form, prefix = '', requiresSslScheme = false }: Props) {
    const { data: locations = [] } = useAllAdminLocations();

    return (
        <div className={'grid grid-cols-1 xl:grid-cols-2 gap-6'}>
            <TitledGreyBox title={'Identity'}>
                <IdentityFields form={form} locations={locations} prefix={prefix} />
            </TitledGreyBox>
            <TitledGreyBox title={'Network'}>
                <NetworkFields form={form} prefix={prefix} requiresSslScheme={requiresSslScheme} />
            </TitledGreyBox>
            <TitledGreyBox title={'Resource Limits'} className={'xl:col-span-2'}>
                <ResourceFields form={form} prefix={prefix} />
            </TitledGreyBox>
            <TitledGreyBox title={'Daemon'} className={'xl:col-span-2'}>
                <DaemonFields form={form} prefix={prefix} />
            </TitledGreyBox>
        </div>
    );
}
