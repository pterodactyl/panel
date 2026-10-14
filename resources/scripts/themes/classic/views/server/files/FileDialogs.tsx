import { Button, cn, Code, Dialog, Form, NewButton, SpinnerOverlay, useAppForm } from '@/sdk';
import type { FileManagerModel } from '@/sdk/theme';

interface DialogProps {
    model: FileManagerModel;
    open: boolean;
    onClose: () => void;
}

/** Renames one file, or moves files to a path relative to the current directory. */
export function RenameDialog({ model, files, move, ...props }: DialogProps & { files: string[]; move?: boolean }) {
    const form = useAppForm({
        defaultValues: { name: files.length > 1 ? '' : files[0] || '' },
        onSubmit: async ({ value: { name } }) => {
            const renamed = move
                ? model.actions.move(files, name)
                : model.actions.rename(files.map((from) => ({ from, to: name })));

            // Failures have already been reported.
            await renamed.catch(() => {});
            props.onClose();
        },
    });

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(isSubmitting) => (
                <Dialog {...props} preventExternalClose={isSubmitting} hideCloseIcon={isSubmitting}>
                    <SpinnerOverlay visible={isSubmitting} />
                    <Form form={form} className='m-0'>
                        <div className={cn('flex flex-wrap', move ? 'items-center' : 'items-end')}>
                            <div className='w-full sm:flex-1 sm:mr-4'>
                                <form.AppField name='name'>
                                    {(field) => (
                                        <field.TextField
                                            type='string'
                                            id='file_name'
                                            label='File Name'
                                            description={
                                                move
                                                    ? 'Enter the new name and directory of this file or folder, relative to the current directory.'
                                                    : undefined
                                            }
                                            autoFocus
                                        />
                                    )}
                                </form.AppField>
                            </div>
                            <div className='w-full sm:w-auto mt-4 sm:mt-0'>
                                <form.AppForm>
                                    <form.SubmitButton className='w-full'>{move ? 'Move' : 'Rename'}</form.SubmitButton>
                                </form.AppForm>
                            </div>
                        </div>
                        {move && (
                            <form.Subscribe selector={(state) => state.values.name}>
                                {(name) => (
                                    <p className='text-xs mt-2 text-muted-foreground'>
                                        <strong className='text-foreground'>New location:</strong>
                                        &nbsp;/home/container/{model.resolve(name)}
                                    </p>
                                )}
                            </form.Subscribe>
                        )}
                    </Form>
                </Dialog>
            )}
        </form.Subscribe>
    );
}

export function ChmodDialog({ model, files, ...props }: DialogProps & { files: { file: string; mode: string }[] }) {
    const form = useAppForm({
        defaultValues: { mode: files.length > 1 ? '' : files[0].mode || '' },
        onSubmit: async ({ value: { mode } }) => {
            // Failures have already been reported.
            await model.actions.chmod(files.map(({ file }) => ({ file, mode }))).catch(() => {});
            props.onClose();
        },
    });

    return (
        <form.Subscribe selector={(state) => state.isSubmitting}>
            {(isSubmitting) => (
                <Dialog {...props} preventExternalClose={isSubmitting} hideCloseIcon={isSubmitting}>
                    <SpinnerOverlay visible={isSubmitting} />
                    <Form form={form} className='m-0'>
                        <div className='flex flex-wrap items-end'>
                            <div className='w-full sm:flex-1 sm:mr-4'>
                                <form.AppField name='mode'>
                                    {(field) => (
                                        <field.TextField type='string' id='file_mode' label='File Mode' autoFocus />
                                    )}
                                </form.AppField>
                            </div>
                            <div className='w-full sm:w-auto mt-4 sm:mt-0'>
                                <form.AppForm>
                                    <form.SubmitButton className='w-full'>Update</form.SubmitButton>
                                </form.AppForm>
                            </div>
                        </div>
                    </Form>
                </Dialog>
            )}
        </form.Subscribe>
    );
}

function NewDirectoryForm({ model, onClose }: { model: FileManagerModel; onClose: () => void }) {
    const form = useAppForm({
        defaultValues: { directoryName: '' },
        onSubmit: async ({ value }) => {
            try {
                await model.actions.createDirectory(value.directoryName);
                onClose();
            } catch {
                // The failure has already been reported.
            }
        },
    });

    return (
        <>
            <Form form={form} className='m-0'>
                <form.AppField
                    name='directoryName'
                    validators={{
                        onChange: ({ value }) =>
                            value.length >= 1 ? undefined : 'A valid directory name must be provided.',
                    }}
                >
                    {(field) => <field.TextField autoFocus id='directoryName' label='Name' />}
                </form.AppField>
                <p className='mt-2 text-sm md:text-base break-all'>
                    <span className='text-foreground'>This directory will be created as&nbsp;</span>
                    <Code>
                        /home/container/
                        <form.Subscribe selector={(state) => state.values.directoryName}>
                            {(name) => <span className='text-accent'>{model.resolve(name)}</span>}
                        </form.Subscribe>
                    </Code>
                </p>
            </Form>
            <Dialog.Footer>
                <Button.Text className='w-full sm:w-auto' onClick={onClose}>
                    Cancel
                </Button.Text>
                <Button className='w-full sm:w-auto' onClick={() => form.handleSubmit()}>
                    Create
                </Button>
            </Dialog.Footer>
        </>
    );
}

export function NewDirectoryButton({ model }: { model: FileManagerModel }) {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New folder</NewButton>}>
            {({ open, onClose }) => (
                <Dialog open={open} onClose={onClose} title='Create folder'>
                    <NewDirectoryForm model={model} onClose={onClose} />
                </Dialog>
            )}
        </Dialog.Trigger>
    );
}
