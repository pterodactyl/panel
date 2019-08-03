import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikActions } from 'formik';
import { FileObject } from '@/api/server/files/loadDirectory';
import Field from '@/components/elements/Field';
import { getDirectoryFromHash } from '@/helpers';
import { join } from 'path';
import renameFile from '@/api/server/files/renameFile';
import { ServerContext } from '@/state/server';

interface FormikValues {
    name: string;
}

export default ({ file, ...props }: RequiredModalProps & { file: FileObject }) => {
    const server = ServerContext.useStoreState(state => state.server.data!);

    const submit = (values: FormikValues, { setSubmitting }: FormikActions<FormikValues>) => {
        const renameFrom = join(getDirectoryFromHash(), file.name);
        const renameTo = join(getDirectoryFromHash(), values.name);

        renameFile(server.uuid, { renameFrom, renameTo })
            .then(() => props.onDismissed())
            .catch(error => {
                setSubmitting(false);
                console.error(error);
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ name: file.name }}
        >
            {({ isSubmitting }) => (
                <Modal {...props} dismissable={!isSubmitting} showSpinnerOverlay={isSubmitting}>
                    <Form className={'m-0'}>
                        <Field
                            type={'string'}
                            id={'file_name'}
                            name={'name'}
                            label={'File Name'}
                            description={'Enter the new name of this file or folder.'}
                        />
                        <div className={'mt-6 text-right'}>
                            <button className={'btn btn-sm btn-primary'}>
                                Rename
                            </button>
                        </div>
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};
