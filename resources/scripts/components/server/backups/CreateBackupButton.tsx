import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { NewButton } from '@/components/elements/NewButton';
import type { WithClassname } from '@/components/types';
import Can from '@/components/elements/Can';
import { useCurrentServerUuid } from '@/api/server/queries';
import { createServerBackupInput, useCreateServerBackup } from '@/api/server/backups/queries';

const CreateBackupDialogContent = ({ onClose }: { onClose: () => void }) => {
    const uuid = useCurrentServerUuid()!;
    const createBackup = useCreateServerBackup();

    const form = useAppForm({
        defaultValues: { name: '', ignored: '', isLocked: false },
        onSubmit: async ({ value }) => {
            try {
                await createBackup.mutateAsync(createServerBackupInput(uuid, value));
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open
            title={'Create server backup'}
            onClose={onClose}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form}>
                <form.AppField
                    name={'name'}
                    validators={{
                        onChange: ({ value }) =>
                            value.length <= 191 ? undefined : 'The backup name must not exceed 191 characters.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            label={'Backup name'}
                            description={'If provided, the name that should be used to reference this backup.'}
                        />
                    )}
                </form.AppField>
                <div className={'mt-6'}>
                    <form.AppField name={'ignored'}>
                        {(field) => (
                            <field.TextAreaField
                                label={'Ignored Files & Directories'}
                                rows={6}
                                description={`
                                            Enter the files or folders to ignore while generating this backup. Leave blank to use
                                            the contents of the .pteroignore file in the root of the server directory if present.
                                            Wildcard matching of files and folders is supported in addition to negating a rule by
                                            prefixing the path with an exclamation point.
                                        `}
                            />
                        )}
                    </form.AppField>
                </div>
                <Can action={'backup.delete'}>
                    <div className={'mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'}>
                        <form.AppField name={'isLocked'}>
                            {(field) => (
                                <field.SwitchField
                                    label={'Locked'}
                                    description={'Prevents this backup from being deleted until explicitly unlocked.'}
                                />
                            )}
                        </form.AppField>
                    </div>
                </Can>
                <div className={'flex justify-end mt-6'}>
                    <form.AppForm>
                        <form.SubmitButton>Start backup</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
};

const CreateBackupButton = ({ className }: WithClassname) => (
    <Dialog.Trigger
        trigger={({ onClick }) => (
            <NewButton className={className} onClick={onClick}>
                New backup
            </NewButton>
        )}
    >
        {({ open, onClose }) => open && <CreateBackupDialogContent onClose={onClose} />}
    </Dialog.Trigger>
);

export default CreateBackupButton;
