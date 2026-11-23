import type { AppForm } from '@/components/form';
import { type AdminLocation, useAllAdminLocations } from '@/api/admin/locations/queries';
import { type NodeFormValues, nodeNumberValidators, validateNodeName } from '@/components/admin/nodes/nodeForm';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useTranslation } from '@/sdk/localization';

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

const IdentityFields = ({ form, locations, prefix }: IdentityFieldsProps) => {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <form.AppField
                name='name'
                validators={{
                    onChange: ({ value }) => validateNodeName(value),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id={`${prefix}name`}
                        label={t('Name')}
                        description={t('A short identifier used to distinguish this node from others.')}
                    />
                )}
            </form.AppField>
            <form.AppField name='description'>
                {(field) => (
                    <field.TextField
                        type='text'
                        id={`${prefix}description`}
                        label={t('Description')}
                        description={t('A longer description of this node.')}
                    />
                )}
            </form.AppField>
            <form.AppField
                name='locationId'
                validators={{
                    onChange: ({ value }) => (value >= 1 ? undefined : t('A location must be selected.')),
                }}
            >
                {(field) => (
                    <field.SelectField
                        id={`${prefix}location_id`}
                        label={t('Location')}
                        options={locations.map((location) => ({
                            value: location.attributes.id,
                            label: location.attributes.short,
                        }))}
                    />
                )}
            </form.AppField>
        </div>
    );
};

const NetworkFields = ({ form, prefix, requiresSslScheme }: NetworkFieldsProps) => {
    const { t } = useTranslation();
    const schemeDescription = requiresSslScheme
        ? t('This Panel is using HTTPS, so browsers require node connections to use SSL.')
        : t('In most cases you should use SSL. Use HTTP only for non-SSL nodes.');

    return (
        <div className='space-y-6'>
            <div className='grid grid-cols-1 lg:grid-cols-[1fr_16rem] gap-6'>
                <form.AppField
                    name='fqdn'
                    validators={{
                        onChange: ({ value }) => (value.length >= 1 ? undefined : t('An FQDN must be provided.')),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`${prefix}fqdn`}
                            label={t('FQDN')}
                            description={t('The domain name or IP address that points to this node.')}
                        />
                    )}
                </form.AppField>
                <form.AppField
                    name='scheme'
                    validators={{
                        onChange: ({ value }) =>
                            requiresSslScheme && value === 'http'
                                ? t('HTTP node connections cannot be used when the Panel is loaded over HTTPS.')
                                : undefined,
                    }}
                >
                    {(field) => (
                        <field.SelectField
                            id={`${prefix}scheme`}
                            label={t('Communicate Over SSL')}
                            options={[
                                { value: 'https', label: t('Use SSL Connection (https)') },
                                {
                                    value: 'http',
                                    label: t('Use HTTP Connection (http)'),
                                    disabled: requiresSslScheme,
                                },
                            ]}
                            description={schemeDescription}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-x-8 gap-y-5'>
                <form.AppField name='public'>
                    {(field) => (
                        <field.SwitchField
                            label={t('Node Visibility')}
                            description={t('Public nodes are available for automatic server deployment.')}
                            controlPosition='end'
                            className='w-full'
                        />
                    )}
                </form.AppField>
                <form.AppField name='behindProxy'>
                    {(field) => (
                        <field.SwitchField
                            label={t('Behind Proxy')}
                            description={t('Enable if this node sits behind a proxy such as Cloudflare.')}
                            controlPosition='end'
                            className='w-full'
                        />
                    )}
                </form.AppField>
                <form.AppField name='maintenanceMode'>
                    {(field) => (
                        <field.SwitchField
                            label={t('Maintenance Mode')}
                            description={t('Servers on a node in maintenance mode cannot be accessed.')}
                            controlPosition='end'
                            className='w-full sm:col-span-2 xl:col-span-1'
                        />
                    )}
                </form.AppField>
            </div>
        </div>
    );
};

const ResourceFields = ({ form, prefix }: FormFieldsProps) => {
    const { t } = useTranslation();
    const validators = nodeNumberValidators(t);

    return (
        <div className='grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6'>
            <form.AppField name='memory' validators={validators.memory}>
                {(field) => <field.NumberField id={`${prefix}memory`} label={t('Memory (MiB)')} min={1} />}
            </form.AppField>
            <form.AppField name='memoryOverallocate' validators={validators.memoryOverallocate}>
                {(field) => (
                    <field.NumberField
                        id={`${prefix}memory_overallocate`}
                        label={t('Memory Overallocate (%)')}
                        min={-1}
                    />
                )}
            </form.AppField>
            <form.AppField name='disk' validators={validators.disk}>
                {(field) => <field.NumberField id={`${prefix}disk`} label={t('Disk Space (MiB)')} min={1} />}
            </form.AppField>
            <form.AppField name='diskOverallocate' validators={validators.diskOverallocate}>
                {(field) => (
                    <field.NumberField id={`${prefix}disk_overallocate`} label={t('Disk Overallocate (%)')} min={-1} />
                )}
            </form.AppField>
        </div>
    );
};

const DaemonFields = ({ form, prefix }: FormFieldsProps) => {
    const { t } = useTranslation();
    const validators = nodeNumberValidators(t);

    return (
        <div className='grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[12rem_12rem_12rem_1fr] gap-6'>
            <form.AppField name='daemonListen' validators={validators.daemonListen}>
                {(field) => (
                    <field.NumberField id={`${prefix}daemon_listen`} label={t('Daemon Port')} min={1} max={65535} />
                )}
            </form.AppField>
            <form.AppField name='daemonSftp' validators={validators.daemonSftp}>
                {(field) => (
                    <field.NumberField id={`${prefix}daemon_sftp`} label={t('Daemon SFTP Port')} min={1} max={65535} />
                )}
            </form.AppField>
            <form.AppField name='uploadSize' validators={validators.uploadSize}>
                {(field) => (
                    <field.NumberField id={`${prefix}upload_size`} label={t('Upload Size Limit (MiB)')} min={1} />
                )}
            </form.AppField>
            <form.AppField name='daemonBase'>
                {(field) => <field.TextField type='text' id={`${prefix}daemon_base`} label={t('Daemon Base Path')} />}
            </form.AppField>
        </div>
    );
};

export default function NodeFormFields({ form, prefix = '', requiresSslScheme = false }: Props) {
    const { t } = useTranslation();
    const { data: locations = [] } = useAllAdminLocations();

    return (
        <div className='grid grid-cols-1 xl:grid-cols-2 gap-6'>
            <TitledGreyBox title={t('Identity')}>
                <IdentityFields form={form} locations={locations} prefix={prefix} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Network')}>
                <NetworkFields form={form} prefix={prefix} requiresSslScheme={requiresSslScheme} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Resource Limits')} className='xl:col-span-2'>
                <ResourceFields form={form} prefix={prefix} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Daemon')} className='xl:col-span-2'>
                <DaemonFields form={form} prefix={prefix} />
            </TitledGreyBox>
        </div>
    );
}
