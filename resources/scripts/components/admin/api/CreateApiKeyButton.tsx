import { useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import type { ApiKeyResource } from '@/api/admin/api-keys/queries';
import { API_KEY_RESOURCES, createAdminApiKeyInput, useCreateAdminApiKey } from '@/api/admin/api-keys/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Label from '@/components/elements/Label';
import Button from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { useTranslation, type Translation } from '@/sdk/localization';

// AdminAcl bitmask values (Read = 1, Write = 2).
const PERMISSION_NONE = 0;
const PERMISSION_READ = 1;
const PERMISSION_READ_WRITE = 3;

const resourceLabels = (t: Translation['t']) =>
    ({
        servers: t('Servers'),
        nodes: t('Nodes'),
        allocations: t('Allocations'),
        users: t('Users'),
        locations: t('Locations'),
        eggs: t('Eggs'),
        database_hosts: t('Database Hosts'),
        server_databases: t('Server Databases'),
    }) satisfies Record<ApiKeyResource, string>;

const initialPermissions = {
    servers: PERMISSION_NONE,
    nodes: PERMISSION_NONE,
    allocations: PERMISSION_NONE,
    users: PERMISSION_NONE,
    locations: PERMISSION_NONE,
    eggs: PERMISSION_NONE,
    database_hosts: PERMISSION_NONE,
    server_databases: PERMISSION_NONE,
} satisfies Record<ApiKeyResource, number>;

const validateMemo = (t: Translation['t'], value: string): string | undefined => {
    if (value.length < 1) {
        return t('A description must be provided.');
    }

    if (value.length > 500) {
        return t('The description must not exceed 500 characters.');
    }

    return undefined;
};

type CreateApiKeyDialogProps = {
    open: boolean;
    onClose: () => void;
    onCreatedSecret: (secret: string) => void;
};

function CreateApiKeyDialog({ open, onClose, onCreatedSecret }: CreateApiKeyDialogProps) {
    const { t } = useTranslation();
    const createApiKey = useCreateAdminApiKey();
    const labels = resourceLabels(t);

    const form = useAppForm({
        defaultValues: { memo: '', permissions: initialPermissions },
        onSubmit: async ({ value }) => {
            try {
                const apiKey = await createApiKey.mutateAsync(createAdminApiKeyInput(value));

                onClose();
                onCreatedSecret(apiKey.meta?.secret_token ?? '');
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title={t('Create application API key')}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <form.AppField
                    name='memo'
                    validators={{
                        onChange: ({ value }) => validateMemo(t, value),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id='memo'
                            label={t('Description')}
                            description={t(
                                'A short note describing what this key is used for. You cannot edit a key after creating it.'
                            )}
                        />
                    )}
                </form.AppField>
                <div className='mt-6'>
                    <Label>{t('Permissions')}</Label>
                    <div className='mt-2 space-y-3'>
                        {API_KEY_RESOURCES.map((resource) => (
                            <div key={resource} className='flex items-center'>
                                <p className='flex-1 text-sm text-foreground'>{labels[resource]}</p>
                                <div className='w-48'>
                                    <form.AppField name={`permissions.${resource}`}>
                                        {(field) => (
                                            <field.SelectField
                                                id={`r_${resource}`}
                                                options={[
                                                    { value: PERMISSION_NONE, label: t('None') },
                                                    { value: PERMISSION_READ, label: t('Read') },
                                                    { value: PERMISSION_READ_WRITE, label: t('Read & Write') },
                                                ]}
                                            />
                                        )}
                                    </form.AppField>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
                <div className='flex flex-wrap justify-end mt-6'>
                    <Button type='button' isSecondary className='w-full sm:w-auto sm:mr-2' onClick={onClose}>
                        {t('Cancel')}
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton className='w-full mt-4 sm:w-auto sm:mt-0'>
                            {t('Create Key')}
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

export default function CreateApiKeyButton() {
    const { t } = useTranslation();
    const [secret, setSecret] = useState('');

    return (
        <>
            <Dialog open={secret.length > 0} title={t('Your new API key')} onClose={() => setSecret('')}>
                <p className='text-sm mb-6'>
                    {t(
                        'The application API key shown below will not be displayed again. Store it somewhere safe before closing this dialog.'
                    )}
                </p>
                <pre className='text-sm bg-muted rounded-sm py-2 px-4 font-mono'>
                    <CopyOnClick text={secret}>
                        <code className='font-mono'>{secret}</code>
                    </CopyOnClick>
                </pre>
                <div className='flex justify-end mt-6'>
                    <Button type='button' onClick={() => setSecret('')}>
                        {t('Close')}
                    </Button>
                </div>
            </Dialog>
            <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>{t('New API key')}</NewButton>}>
                {({ open, onClose }) => (
                    <CreateApiKeyDialog open={open} onClose={onClose} onCreatedSecret={setSecret} />
                )}
            </Dialog.Trigger>
        </>
    );
}
