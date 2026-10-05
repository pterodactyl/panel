import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import type { DialogProps } from '@/components/elements/dialog';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useServerDirectory, useServerStore } from '@/state/server';
import { useCurrentServerUuid } from '@/api/server/queries';
import { chmodFilesInput, useChmodFiles } from '@/api/server/files/queries';

interface File {
    file: string;
    mode: string;
}

type OwnProps = DialogProps & { files: File[] };

const ChmodFileModal = ({ files, ...props }: OwnProps) => {
    const uuid = useCurrentServerUuid()!;
    const directory = useServerDirectory();
    const clearSelectedFiles = useServerStore((state) => state.files.clearSelectedFiles);
    const chmodFiles = useChmodFiles();

    const form = useAppForm({
        defaultValues: { mode: files.length > 1 ? '' : files[0].mode || '' },
        onSubmit: async ({ value: { mode } }) => {
            const data = files.map((f) => ({ file: f.file, mode: mode }));

            try {
                await chmodFiles.mutateAsync(chmodFilesInput(uuid, directory, data));
                clearSelectedFiles();
            } catch {
                // Error toast is handled by the mutation.
            }
            props.onClose();
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog {...props} preventExternalClose={isSubmitting} hideCloseIcon={isSubmitting}>
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className={'m-0'}>
                <div className={'flex flex-wrap items-end'}>
                    <div className={'w-full sm:flex-1 sm:mr-4'}>
                        <form.AppField name={'mode'}>
                            {(field) => (
                                <field.TextField type={'string'} id={'file_mode'} label={'File Mode'} autoFocus />
                            )}
                        </form.AppField>
                    </div>
                    <div className={'w-full sm:w-auto mt-4 sm:mt-0'}>
                        <form.AppForm>
                            <form.SubmitButton className={'w-full'}>Update</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </Dialog>
    );
};

export default ChmodFileModal;
