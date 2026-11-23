import { useAppForm, Form } from '@/components/form';
import { type AdminNode, updateAdminNodeInput, useUpdateAdminNode } from '@/api/admin/nodes/queries';
import { useAllAdminLocations } from '@/api/admin/locations/queries';
import {
    type NodeFormValues,
    nodeFormValues,
    nodeNumberValidators,
    nodeValuesFromForm,
    validateNodeName,
} from '@/components/admin/nodes/nodeForm';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { initialExtensionValues, useExtensionPayload } from '@/extensions/forms';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useTranslation } from '@/sdk/localization';

interface Values extends NodeFormValues {
    resetSecret: boolean;
}

const nodeToValues = (node: AdminNode): Values => ({ ...nodeFormValues(node), resetSecret: false });

const NodeSettingsForm = () => {
    const { t } = useTranslation();
    const validators = nodeNumberValidators(t);
    const { node } = useNodeDetail();
    const updateNode = useUpdateAdminNode();

    const { data: locations = [] } = useAllAdminLocations();

    const { withExtensionPayload } = useExtensionPayload('admin.node');
    const form = useAppForm({
        defaultValues: nodeToValues(node),
        onSubmit: async ({ value }) => {
            const { resetSecret, ...values } = value;

            try {
                await updateNode.mutateAsync(
                    updateAdminNodeInput(
                        node.attributes.id,
                        nodeValuesFromForm(withExtensionPayload(values, node)),
                        resetSecret
                    )
                );
                form.setFieldValue('resetSecret', false);
                form.setFieldValue('extensions', initialExtensionValues());
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <Form form={form}>
            <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
                <TitledGreyBox title={t('Settings')}>
                    <form.AppField
                        name='name'
                        validators={{
                            onChange: ({ value }) => validateNodeName(value),
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='name'
                                label={t('Node Name')}
                                description={t('A short identifier used to distinguish this node from others.')}
                            />
                        )}
                    </form.AppField>
                    <div className='mt-6'>
                        <form.AppField name='description'>
                            {(field) => <field.TextField type='text' id='description' label={t('Description')} />}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField
                            name='locationId'
                            validators={{
                                onChange: ({ value }) => (value >= 1 ? undefined : t('A location must be selected.')),
                            }}
                        >
                            {(field) => (
                                <field.SelectField
                                    id='location_id'
                                    label={t('Location')}
                                    options={locations.map((location) => ({
                                        value: location.attributes.id,
                                        label: location.attributes.long
                                            ? `${location.attributes.long} (${location.attributes.short})`
                                            : location.attributes.short,
                                    }))}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField
                            name='fqdn'
                            validators={{
                                onChange: ({ value }) =>
                                    value.length >= 1 ? undefined : t('An FQDN must be provided.'),
                            }}
                        >
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='fqdn'
                                    label={t('FQDN')}
                                    description={t('The domain name or IP address that points to this node.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='scheme'>
                            {(field) => (
                                <field.SelectField
                                    id='scheme'
                                    label={t('Communicate Over SSL')}
                                    options={[
                                        { value: 'https', label: t('Use SSL Connection (https)') },
                                        { value: 'http', label: t('Use HTTP Connection (http)') },
                                    ]}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='public'>
                            {(field) => (
                                <field.SwitchField
                                    label={t('Allow Automatic Allocation')}
                                    description={t('Public nodes are available for automatic server deployment.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='behindProxy'>
                            {(field) => (
                                <field.SwitchField
                                    label={t('Behind Proxy')}
                                    description={t('Enable if this node sits behind a proxy such as Cloudflare.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div className='mt-6'>
                        <form.AppField name='maintenanceMode'>
                            {(field) => (
                                <field.SwitchField
                                    label={t('Maintenance Mode')}
                                    description={t('Servers on a node in maintenance mode cannot be accessed.')}
                                />
                            )}
                        </form.AppField>
                    </div>
                </TitledGreyBox>
                <div className='space-y-6'>
                    <TitledGreyBox title={t('Allocation Limits')}>
                        <div className='grid grid-cols-2 gap-4'>
                            <form.AppField name='memory' validators={validators.memory}>
                                {(field) => <field.NumberField id='memory' label={t('Total Memory (MiB)')} min={1} />}
                            </form.AppField>
                            <form.AppField name='memoryOverallocate' validators={validators.memoryOverallocate}>
                                {(field) => (
                                    <field.NumberField
                                        id='memory_overallocate'
                                        label={t('Overallocate (%)')}
                                        min={-1}
                                    />
                                )}
                            </form.AppField>
                            <form.AppField name='disk' validators={validators.disk}>
                                {(field) => <field.NumberField id='disk' label={t('Disk Space (MiB)')} min={1} />}
                            </form.AppField>
                            <form.AppField name='diskOverallocate' validators={validators.diskOverallocate}>
                                {(field) => (
                                    <field.NumberField id='disk_overallocate' label={t('Overallocate (%)')} min={-1} />
                                )}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                    <TitledGreyBox title={t('General Configuration')}>
                        <form.AppField name='uploadSize' validators={validators.uploadSize}>
                            {(field) => (
                                <field.NumberField
                                    id='upload_size'
                                    label={t('Maximum Web Upload Filesize (MiB)')}
                                    min={1}
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6 grid grid-cols-2 gap-4'>
                            <form.AppField name='daemonListen' validators={validators.daemonListen}>
                                {(field) => (
                                    <field.NumberField
                                        id='daemon_listen'
                                        label={t('Daemon Port')}
                                        min={1}
                                        max={65535}
                                    />
                                )}
                            </form.AppField>
                            <form.AppField name='daemonSftp' validators={validators.daemonSftp}>
                                {(field) => (
                                    <field.NumberField
                                        id='daemon_sftp'
                                        label={t('Daemon SFTP Port')}
                                        min={1}
                                        max={65535}
                                    />
                                )}
                            </form.AppField>
                        </div>
                        <div className='mt-6'>
                            <form.AppField name='daemonBase'>
                                {(field) => (
                                    <field.TextField type='text' id='daemon_base' label={t('Daemon Base Path')} />
                                )}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                </div>
            </div>
            <form.AppField name='extensions'>
                {() => (
                    <ExtensionFormFields
                        form='admin.node'
                        mode='edit'
                        resource={node}
                        error={updateNode.error}
                        boxed
                        className='mt-6'
                    />
                )}
            </form.AppField>
            <TitledGreyBox title={t('Save Settings')} className='mt-6'>
                <form.AppField name='resetSecret'>
                    {(field) => (
                        <field.SwitchField
                            label={t('Reset Daemon Master Key')}
                            description={t(
                                'Resetting the daemon master key voids any request using the old key. This key is used for all sensitive daemon operations including server creation and deletion.'
                            )}
                        />
                    )}
                </form.AppField>
                <div className='mt-6 text-right'>
                    <form.AppForm>
                        <form.SubmitButton>{t('Save Changes')}</form.SubmitButton>
                    </form.AppForm>
                </div>
            </TitledGreyBox>
        </Form>
    );
};

export default function NodeSettingsTab() {
    const { node } = useNodeDetail();

    return <NodeSettingsForm key={node.attributes.id} />;
}
