import { useStore } from '@tanstack/react-form';
import i18next from 'i18next';
import { useAppForm, Form } from '@/components/form';
import { createAdminServerDatabaseInput, useCreateAdminServerDatabase } from '@/api/admin/servers/queries';
import { createServerDatabaseBodyFromFormValues } from '@/components/admin/servers/helpers';
import { useAllAdminDatabaseHosts } from '@/api/admin/database-hosts/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

interface Props {
    serverId: number;
}

type CreateServerDatabaseDialogProps = Props & {
    open: boolean;
    onClose: () => void;
};

const validateDatabaseName = ({ value }: { value: string }): string | undefined => {
    if (value.length < 1) {
        return i18next.t('A database name must be provided.');
    }

    if (value.length > 48) {
        return i18next.t('Database name must not exceed 48 characters.');
    }

    return undefined;
};

function CreateServerDatabaseDialog({ serverId, open, onClose }: CreateServerDatabaseDialogProps) {
    const { t } = useTranslation();
    const createDatabase = useCreateAdminServerDatabase();

    const { data: hosts = [] } = useAllAdminDatabaseHosts({ enabled: open });

    const form = useAppForm({
        defaultValues: { databaseName: '', connectionsFrom: '', databaseHostId: 0, maxConnections: '' },
        onSubmit: async ({ value }) => {
            try {
                await createDatabase.mutateAsync(
                    createAdminServerDatabaseInput(
                        serverId,
                        createServerDatabaseBodyFromFormValues({
                            databaseName: value.databaseName,
                            connectionsFrom: value.connectionsFrom || '%',
                            databaseHostId: Number(value.databaseHostId),
                            maxConnections: value.maxConnections === '' ? null : Number(value.maxConnections),
                        })
                    )
                );
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title={t('Create new database')}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <form.AppField name='databaseName' validators={{ onChange: validateDatabaseName }}>
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='database_name'
                            label={t('Database Name')}
                            description={t('A descriptive name for this database instance.')}
                        />
                    )}
                </form.AppField>
                <div className='mt-6'>
                    <form.AppField name='databaseHostId'>
                        {(field) => (
                            <field.SelectField
                                id='database_host_id'
                                label={t('Database Host')}
                                description={t('The host this database will be created on.')}
                                options={[
                                    { value: 0, label: t('Select a database host...'), disabled: true },
                                    ...hosts.map((host) => ({
                                        value: host.attributes.id,
                                        label: `${host.attributes.name} (${host.attributes.host}:${host.attributes.port})`,
                                    })),
                                ]}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='connectionsFrom'>
                        {(field) => (
                            <field.TextField
                                type='text'
                                id='connections_from'
                                label={t('Connections From')}
                                description={t(
                                    'Where connections should be allowed from. Leave blank to allow connections from anywhere.'
                                )}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='maxConnections'>
                        {(field) => (
                            <field.TextField
                                type='number'
                                min={0}
                                id='max_connections'
                                label={t('Max Connections')}
                                description={t('Maximum simultaneous connections allowed for this database user.')}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='flex flex-wrap justify-end mt-6'>
                    <Button type='button' isSecondary className='w-full sm:w-auto sm:mr-2' onClick={onClose}>
                        {t('Cancel')}
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton className='w-full mt-4 sm:w-auto sm:mt-0'>
                            {t('Create Database')}
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

export default function CreateServerDatabaseButton(props: Props) {
    const { t } = useTranslation();

    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>{t('New database')}</NewButton>}>
            {({ open, onClose }) => <CreateServerDatabaseDialog {...props} open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
