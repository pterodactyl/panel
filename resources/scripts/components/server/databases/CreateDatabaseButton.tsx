import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCurrentServerUuid } from '@/api/server/queries';
import { createServerDatabaseInput, useCreateServerDatabase } from '@/api/server/databases/queries';
import Button from '@/components/elements/Button';
import { NewButton } from '@/components/elements/NewButton';
import type { WithClassname } from '@/components/types';

const CreateDatabaseDialogContent = ({ onClose }: { onClose: () => void }) => {
    const uuid = useCurrentServerUuid()!;
    const createDatabase = useCreateServerDatabase();

    const form = useAppForm({
        defaultValues: { databaseName: '', connectionsFrom: '' },
        onSubmit: async ({ value }) => {
            try {
                await createDatabase.mutateAsync(
                    createServerDatabaseInput(uuid, {
                        databaseName: value.databaseName,
                        connectionsFrom: value.connectionsFrom || '%',
                    })
                );
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting) || createDatabase.isPending;

    return (
        <Dialog
            open
            title={'Create new database'}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={() => {
                form.reset();
                onClose();
            }}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className={'m-0'}>
                <form.AppField
                    name={'databaseName'}
                    validators={{
                        onChange: ({ value }) =>
                            value.length < 1
                                ? 'A database name must be provided.'
                                : value.length < 3
                                  ? 'Database name must be at least 3 characters.'
                                  : value.length > 48
                                    ? 'Database name must not exceed 48 characters.'
                                    : /^[\w\-.]{3,48}$/.test(value)
                                      ? undefined
                                      : 'Database name should only contain alphanumeric characters, underscores, dashes, and/or periods.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type={'string'}
                            id={'database_name'}
                            label={'Database Name'}
                            description={'A descriptive name for your database instance.'}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6'}>
                    <form.AppField
                        name={'connectionsFrom'}
                        validators={{
                            onChange: ({ value }) =>
                                /^[\w\-/.%:]*$/.test(value) ? undefined : 'A valid host address must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'string'}
                                id={'connections_from'}
                                label={'Connections From'}
                                description={
                                    'Where connections should be allowed from. Leave blank to allow connections from anywhere.'
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'flex flex-wrap justify-end mt-6'}>
                    <Button type={'button'} isSecondary className={'w-full sm:w-auto sm:mr-2'} onClick={onClose}>
                        Cancel
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton className={'w-full mt-4 sm:w-auto sm:mt-0'}>
                            Create Database
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
};

const CreateDatabaseButton = ({ className }: WithClassname) => (
    <Dialog.Trigger
        trigger={({ onClick }) => (
            <NewButton className={className} onClick={onClick}>
                New database
            </NewButton>
        )}
    >
        {({ open, onClose }) => open && <CreateDatabaseDialogContent onClose={onClose} />}
    </Dialog.Trigger>
);

export default CreateDatabaseButton;
