import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'pathe';
import renameFiles from '@/api/server/files/renameFiles';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import useFlash from '@/plugins/useFlash';

interface FormikValues {
    name: string;
}

type OwnProps = RequiredModalProps & { files: string[]; useMoveTerminology?: boolean };

const RenameFileModal = ({ files, useMoveTerminology, ...props }: OwnProps) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { mutate } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const setSelectedFiles = ServerContext.useStoreActions(actions => actions.files.setSelectedFiles);

    const submit = ({ name }: FormikValues, { setSubmitting }: FormikHelpers<FormikValues>) => {
        clearFlashes('files');

        const len = name.split('/').length;
        if (files.length === 1) {
            if (!useMoveTerminology && len === 1) {
                // Rename the file within this directory.
                mutate(data => data!.map(f => (f.name === files[0] ? { ...f, name } : f)), false);
            } else if (useMoveTerminology || len > 1) {
                // Remove the file from this directory since they moved it elsewhere.
                mutate(data => data!.filter(f => f.name !== files[0]), false);
            }
        }

        let data;
        if (useMoveTerminology && files.length > 1) {
            data = files.map(f => ({ from: f, to: join(name, f) }));
        } else {
            data = files.map(f => ({ from: f, to: name }));
        }

        renameFiles(uuid, directory, data)
            .then((): Promise<any> => (files.length > 0 ? mutate() : Promise.resolve()))
            .then(() => setSelectedFiles([]))
            .catch(error => {
                mutate();
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files', error });
            })
            .then(() => props.onDismissed());
    };

    return (
        <Formik onSubmit={submit} initialValues={{ name: files.length > 1 ? '' : files[0] || '' }}>
            {({ isSubmitting, values }) => (
                <Modal {...props} dismissable={!isSubmitting} showSpinnerOverlay={isSubmitting}>
                    <Form css={tw`m-0`}>
                        <div css={[tw`flex flex-wrap`, useMoveTerminology ? tw`items-center` : tw`items-end`]}>
                            <div css={tw`w-full sm:flex-1 sm:mr-4`}>
                                <Field
                                    type={'string'}
                                    id={'file_name'}
                                    name={'name'}
                                    label={'File Name'}
                                    description={
                                        useMoveTerminology
                                            ? 'Enter the new name and directory of this file or folder, relative to the current directory.'
                                            : undefined
                                    }
                                    autoFocus
                                />
                            </div>
                            <div css={tw`w-full sm:w-auto mt-4 sm:mt-0`}>
                                <Button css={tw`w-full`}>{useMoveTerminology ? 'Move' : 'Rename'}</Button>
                            </div>
                        </div>
                        {useMoveTerminology && (
                            <p css={tw`text-xs mt-2 text-neutral-400`}>
                                <strong css={tw`text-neutral-200`}>New location:</strong>
                                &nbsp;/home/container/{join(directory, values.name).replace(/^(\.\.\/|\/)+/, '')}
                            </p>
                        )}
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};

export default RenameFileModal;
