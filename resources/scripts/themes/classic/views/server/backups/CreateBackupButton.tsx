import { Dialog, Form, NewButton, SpinnerOverlay, useAppForm, type BackupListModel } from '@/sdk';

const ignoredDescription = [
    'Enter the files or folders to ignore while generating this backup.',
    'Leave blank to use the contents of the .pteroignore file in the root of the server directory if present.',
    'Wildcard matching of files and folders is supported in addition to negating a rule by prefixing the path',
    'with an exclamation point.',
].join(' ');

function CreateBackupDialog({ model, onClose }: { model: BackupListModel; onClose: () => void }) {
    const form = useAppForm({
        defaultValues: { name: '', ignored: '', isLocked: false },
        onSubmit: async ({ value }) => {
            if (await model.actions.create(value)) {
                onClose();
            }
        },
    });

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(submitting) => (
                <Dialog
                    open
                    title='Create server backup'
                    onClose={onClose}
                    preventExternalClose={submitting}
                    hideCloseIcon={submitting}
                >
                    <SpinnerOverlay visible={submitting} />
                    <Form form={form}>
                        <form.AppField name='name' validators={{ onChange: ({ value }) => model.validateName(value) }}>
                            {(field) => (
                                <field.TextField
                                    label='Backup name'
                                    description='If provided, the name that should be used to reference this backup.'
                                />
                            )}
                        </form.AppField>
                        <div className='mt-6'>
                            <form.AppField name='ignored'>
                                {(field) => (
                                    <field.TextAreaField
                                        label='Ignored Files & Directories'
                                        rows={6}
                                        description={ignoredDescription}
                                    />
                                )}
                            </form.AppField>
                        </div>
                        {model.permissions.delete && (
                            <div className='mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'>
                                <form.AppField name='isLocked'>
                                    {(field) => (
                                        <field.SwitchField
                                            label='Locked'
                                            description='Prevents this backup from being deleted until explicitly unlocked.'
                                        />
                                    )}
                                </form.AppField>
                            </div>
                        )}
                        <div className='flex justify-end mt-6'>
                            <form.AppForm>
                                <form.SubmitButton>Start backup</form.SubmitButton>
                            </form.AppForm>
                        </div>
                    </Form>
                </Dialog>
            )}
        </form.Subscribe>
    );
}

export default function CreateBackupButton({ model }: { model: BackupListModel }) {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New backup</NewButton>}>
            {({ open, onClose }) => open && <CreateBackupDialog model={model} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
