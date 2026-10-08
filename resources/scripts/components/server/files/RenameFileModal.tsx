import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import type { DialogProps } from '@/components/elements/dialog';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { join } from 'pathe';
import { useServerDirectory, useServerStore } from '@/state/server';
import { cn } from '@/lib/cn';
import { useCurrentServerUuid } from '@/api/server/queries';
import { renameFilesInput, useRenameFiles } from '@/api/server/files/queries';

type OwnProps = DialogProps & { files: string[]; useMoveTerminology?: boolean };

const RenameFileModal = ({ files, useMoveTerminology, ...props }: OwnProps) => {
    const uuid = useCurrentServerUuid()!;
    const directory = useServerDirectory();
    const clearSelectedFiles = useServerStore((state) => state.files.clearSelectedFiles);
    const renameFiles = useRenameFiles();

    const form = useAppForm({
        defaultValues: { name: files.length > 1 ? '' : files[0] || '' },
        onSubmit: async ({ value: { name } }) => {
            const data = files.map((f) => ({
                from: f,
                to: useMoveTerminology && files.length > 1 ? join(name, f) : name,
            }));

            try {
                await renameFiles.mutateAsync(renameFilesInput(uuid, directory, data));
                clearSelectedFiles();
            } catch {
                // Error toast is handled by the mutation.
            }

            props.onClose();
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);
    const name = useStore(form.store, (state) => state.values.name);

    return (
        <Dialog {...props} preventExternalClose={isSubmitting} hideCloseIcon={isSubmitting}>
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <div className={cn('flex flex-wrap', useMoveTerminology ? 'items-center' : 'items-end')}>
                    <div className='w-full sm:flex-1 sm:mr-4'>
                        <form.AppField name='name'>
                            {(field) => (
                                <field.TextField
                                    type='string'
                                    id='file_name'
                                    label='File Name'
                                    description={
                                        useMoveTerminology
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
                            <form.SubmitButton className='w-full'>
                                {useMoveTerminology ? 'Move' : 'Rename'}
                            </form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
                {useMoveTerminology && (
                    <p className='text-xs mt-2 text-muted-foreground'>
                        <strong className='text-foreground'>New location:</strong>
                        &nbsp;/home/container/{join(directory, name).replace(/^(\.\.\/|\/)+/, '')}
                    </p>
                )}
            </Form>
        </Dialog>
    );
};

export default RenameFileModal;
