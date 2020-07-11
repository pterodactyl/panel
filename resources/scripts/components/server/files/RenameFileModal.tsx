import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'path';
import renameFiles from '@/api/server/files/renameFiles';
import { ServerContext } from '@/state/server';
import { FileObject } from '@/api/server/files/loadDirectory';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useServer from '@/plugins/useServer';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import useFlash from '@/plugins/useFlash';

interface FormikValues {
    name: string;
}

type Props = RequiredModalProps & { file: FileObject; useMoveTerminology?: boolean };

export default ({ file, useMoveTerminology, ...props }: Props) => {
    const { uuid } = useServer();
    const { mutate } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const submit = ({ name }: FormikValues, { setSubmitting }: FormikHelpers<FormikValues>) => {
        clearFlashes('files');

        const len = name.split('/').length;
        if (!useMoveTerminology && len === 1) {
            // Rename the file within this directory.
            mutate(files => files.map(f => f.uuid === file.uuid ? { ...f, name } : f), false);
        } else if ((useMoveTerminology || len > 1) && file.uuid.length) {
            // Remove the file from this directory since they moved it elsewhere.
            mutate(files => files.filter(f => f.uuid !== file.uuid), false);
        }

        const renameFrom = join(directory, file.name);
        const renameTo = join(directory, name);

        renameFiles(uuid, directory, [ { renameFrom, renameTo } ])
            .then(() => props.onDismissed())
            .catch(error => {
                mutate();
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files', error });
            });
    };

    return (
        <Formik onSubmit={submit} initialValues={{ name: file.name }}>
            {({ isSubmitting, values }) => (
                <Modal {...props} dismissable={!isSubmitting} showSpinnerOverlay={isSubmitting}>
                    <Form css={tw`m-0`}>
                        <div
                            css={[
                                tw`flex`,
                                useMoveTerminology ? tw`items-center` : tw`items-end`,
                            ]}
                        >
                            <div css={tw`flex-1 mr-6`}>
                                <Field
                                    type={'string'}
                                    id={'file_name'}
                                    name={'name'}
                                    label={'File Name'}
                                    description={useMoveTerminology
                                        ? 'Enter the new name and directory of this file or folder, relative to the current directory.'
                                        : undefined
                                    }
                                    autoFocus
                                />
                            </div>
                            <div>
                                <Button>{useMoveTerminology ? 'Move' : 'Rename'}</Button>
                            </div>
                        </div>
                        {useMoveTerminology &&
                        <p css={tw`text-xs mt-2 text-neutral-400`}>
                            <strong css={tw`text-neutral-200`}>New location:</strong>
                            &nbsp;/home/container/{join(directory, values.name).replace(/^(\.\.\/|\/)+/, '')}
                        </p>
                        }
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};
