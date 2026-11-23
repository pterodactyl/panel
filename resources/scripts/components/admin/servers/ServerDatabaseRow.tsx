import { useStore } from '@tanstack/react-form';
import { jdbcConnectionString, serverDatabaseShortName } from '@/lib/databases';
import { Eye } from 'lucide-react';
import { useAppForm, Form, type AppForm } from '@/components/form';
import {
    type AdminServerDatabase,
    deleteAdminServerDatabaseInput,
    rotateAdminServerDatabasePasswordInput,
    useDeleteAdminServerDatabase,
    useRotateAdminServerDatabasePassword,
} from '@/api/admin/servers/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Label from '@/components/elements/Label';
import { TextInput } from '@/components/form/controls';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import SecretInput from '@/components/elements/SecretInput';
import { DeleteAction, RowActionButton, RowActions } from '@/components/elements/table/RowActions';
import { relationshipAttributes } from '@/api/relationships';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

export interface ServerDatabaseRowProps {
    serverId: number;
    database: AdminServerDatabase;
}

type ServerDatabaseDialogProps = ServerDatabaseRowProps & {
    open: boolean;
    onClose: () => void;
};

function DeleteDatabaseConfirmForm({
    form,
    databaseName,
    onCancel,
}: {
    form: AppForm<{ confirm: string }>;
    databaseName: string;
    onCancel: () => void;
}) {
    const { t } = useTranslation();

    return (
        <Form form={form} className='m-0 mt-6'>
            <form.AppField
                name='confirm'
                validators={{
                    onChange: ({ value }) =>
                        value === databaseName || value === serverDatabaseShortName(databaseName)
                            ? undefined
                            : t('The database name must be provided.'),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='confirm_name'
                        label={t('Confirm Database Name')}
                        description={t('Enter the database name to confirm deletion.')}
                    />
                )}
            </form.AppField>
            <div className='mt-6 text-right'>
                <Button type='button' isSecondary className='mr-2' onClick={onCancel}>
                    {t('Cancel')}
                </Button>
                <form.AppForm>
                    <form.SubmitButton color='red'>{t('Delete Database')}</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

function DeleteServerDatabaseDialog({ serverId, database, open, onClose }: ServerDatabaseDialogProps) {
    const { t } = useTranslation();
    const deleteDatabase = useDeleteAdminServerDatabase();
    const databaseAttributes = database.attributes;

    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteDatabase.mutateAsync(
                    deleteAdminServerDatabaseInput(serverId, databaseAttributes.id, databaseAttributes.name)
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
            title={t('Confirm database deletion')}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                <Translate
                    i18nKey='Deleting a database is a permanent action, it cannot be undone. This will permanently delete the <bold>{{name}}</bold> database and remove all associated data.'
                    values={{ name: databaseAttributes.name }}
                    components={{ bold: <strong /> }}
                />
            </p>
            <DeleteDatabaseConfirmForm form={form} databaseName={databaseAttributes.name} onCancel={onClose} />
        </Dialog>
    );
}

function ServerDatabaseConnectionDialog({ serverId, database, open, onClose }: ServerDatabaseDialogProps) {
    const { t } = useTranslation();
    const rotatePassword = useRotateAdminServerDatabasePassword();
    const databaseAttributes = database.attributes;
    const connectionString = `${databaseAttributes.host.address}:${databaseAttributes.host.port}`;
    const password = relationshipAttributes(databaseAttributes.relationships?.password)?.password;

    const jdbcConnection = jdbcConnectionString({
        connectionString,
        name: databaseAttributes.name,
        password,
        username: databaseAttributes.username,
    });

    const rotate = () => {
        rotatePassword.mutate(rotateAdminServerDatabasePasswordInput(serverId, databaseAttributes.id));
    };

    return (
        <Dialog open={open} title={t('Database connection details')} onClose={onClose}>
            <div>
                <Label>{t('Endpoint')}</Label>
                <CopyOnClick text={connectionString}>
                    <TextInput type='text' readOnly value={connectionString} />
                </CopyOnClick>
            </div>
            <div className='mt-6'>
                <Label>{t('Connections from')}</Label>
                <TextInput type='text' readOnly value={databaseAttributes.connections_from} />
            </div>
            <div className='mt-6'>
                <Label>{t('Max Connections')}</Label>
                <TextInput type='text' readOnly value={databaseAttributes.max_connections ?? t('Unlimited')} />
            </div>
            <div className='mt-6'>
                <Label>{t('Username')}</Label>
                <CopyOnClick text={databaseAttributes.username}>
                    <TextInput type='text' readOnly value={databaseAttributes.username} />
                </CopyOnClick>
            </div>
            <div className='mt-6'>
                <Label>{t('Password')}</Label>
                <SecretInput value={password} label={t('Password')} />
            </div>
            <div className='mt-6'>
                <Label>{t('JDBC Connection String')}</Label>
                <CopyOnClick text={jdbcConnection} showInNotification={false}>
                    <TextInput type='text' readOnly value={jdbcConnection} />
                </CopyOnClick>
            </div>
            <div className='mt-6 text-right'>
                <Button
                    isSecondary
                    color='primary'
                    className='mr-2'
                    onClick={rotate}
                    isLoading={rotatePassword.isPending}
                >
                    {t('Rotate Password')}
                </Button>
                <Button isSecondary onClick={onClose}>
                    {t('Close')}
                </Button>
            </div>
        </Dialog>
    );
}

export function ServerDatabaseActions({ serverId, database }: ServerDatabaseRowProps) {
    const { t } = useTranslation();
    const { name } = database.attributes;

    return (
        <RowActions>
            <Dialog.Trigger
                trigger={({ onClick }) => (
                    <RowActionButton
                        icon={Eye}
                        label={t('Connection details')}
                        aria-label={t('View {{name}} connection details', { name })}
                        onClick={onClick}
                    />
                )}
            >
                {({ open, onClose }) => (
                    <ServerDatabaseConnectionDialog
                        serverId={serverId}
                        database={database}
                        open={open}
                        onClose={onClose}
                    />
                )}
            </Dialog.Trigger>
            <Dialog.Trigger
                trigger={({ onClick }) => (
                    <DeleteAction aria-label={t('Delete {{name}}', { name })} onClick={onClick} />
                )}
            >
                {({ open, onClose }) => (
                    <DeleteServerDatabaseDialog serverId={serverId} database={database} open={open} onClose={onClose} />
                )}
            </Dialog.Trigger>
        </RowActions>
    );
}
