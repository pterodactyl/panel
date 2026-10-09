import { useStore } from '@tanstack/react-form';
import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { createAdminDatabaseHostInput, useCreateAdminDatabaseHost } from '@/api/admin/database-hosts/queries';
import {
    databaseHostBodyFromFormValues,
    databaseHostValidators,
    newDatabaseHostFormValues,
} from '@/components/admin/databases/databaseHostForm';
import DatabaseHostNodeSelect from '@/components/admin/databases/DatabaseHostNodeSelect';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { useExtensionPayload } from '@/extensions/forms';
import Icon from '@/components/elements/Icon';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useAppForm, Form } from '@/components/form';

export default function CreateDatabaseHostForm() {
    const navigate = useNavigate();
    const createDatabaseHost = useCreateAdminDatabaseHost();

    const { withExtensionPayload } = useExtensionPayload('admin.database_host');
    const form = useAppForm({
        defaultValues: newDatabaseHostFormValues(),
        onSubmit: async ({ value }) => {
            try {
                const host = await createDatabaseHost.mutateAsync(
                    createAdminDatabaseHostInput(databaseHostBodyFromFormValues(withExtensionPayload(value)))
                );

                void navigate({ to: '/panel/databases/$id', params: { id: host.attributes.id } });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const nodeId = useStore(form.store, (state) => state.values.nodeId);

    return (
        <AdminContentBlock
            title='Admin · Create Database Host'
            heading='Create Database Host'
            description='Add a database host for server databases.'
        >
            <Link
                to='/panel/databases'
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                Back to Database Hosts
            </Link>
            <Form form={form}>
                <div className='space-y-6'>
                    <TitledGreyBox title='Host Details'>
                        <div className='space-y-6'>
                            <form.AppField name='name' validators={databaseHostValidators.name}>
                                {(field) => (
                                    <field.TextField
                                        type='text'
                                        id='name'
                                        label='Name'
                                        description='A short, human-readable name used to identify this host.'
                                    />
                                )}
                            </form.AppField>
                            <div className='grid grid-cols-1 sm:grid-cols-[1fr_8rem] gap-6'>
                                <form.AppField name='host' validators={databaseHostValidators.host}>
                                    {(field) => (
                                        <field.TextField
                                            type='text'
                                            id='host'
                                            label='Host'
                                            description='The hostname or IP address that this database server is reachable on.'
                                        />
                                    )}
                                </form.AppField>
                                <form.AppField name='port' validators={databaseHostValidators.port}>
                                    {(field) => <field.NumberField id='port' label='Port' />}
                                </form.AppField>
                            </div>
                            <DatabaseHostNodeSelect
                                id='nodeId'
                                value={nodeId}
                                onChange={(value) => form.setFieldValue('nodeId', value)}
                            />
                        </div>
                    </TitledGreyBox>
                    <TitledGreyBox title='Credentials'>
                        <form.AppField name='username' validators={databaseHostValidators.username}>
                            {(field) => (
                                <field.TextField
                                    type='text'
                                    id='username'
                                    label='Username'
                                    description='A user on the database server with permission to create users and databases.'
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6'>
                            <form.AppField name='password' validators={databaseHostValidators.requiredPassword}>
                                {(field) => (
                                    <field.TextField
                                        type='password'
                                        id='password'
                                        label='Password'
                                        description='The password for the account used to connect to this host.'
                                    />
                                )}
                            </form.AppField>
                        </div>
                    </TitledGreyBox>
                    <form.AppField name='extensions'>
                        {() => (
                            <ExtensionFormFields
                                form='admin.database_host'
                                mode='create'
                                error={createDatabaseHost.error}
                                boxed
                            />
                        )}
                    </form.AppField>
                    <div className='flex justify-end'>
                        <form.AppForm>
                            <form.SubmitButton>Create Host</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </AdminContentBlock>
    );
}
