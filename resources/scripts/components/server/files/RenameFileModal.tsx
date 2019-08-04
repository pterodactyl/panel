import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikActions } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'path';
import renameFile from '@/api/server/files/renameFile';
import { ServerContext } from '@/state/server';
import { FileObject } from '@/api/server/files/loadDirectory';

interface FormikValues {
    name: string;
}

type Props = RequiredModalProps & { file: FileObject };

export default ({ file, ...props }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const pushFile = ServerContext.useStoreActions(actions => actions.files.pushFile);

    const submit = (values: FormikValues, { setSubmitting }: FormikActions<FormikValues>) => {
        const renameFrom = join(directory, file.name);
        const renameTo = join(directory, values.name);

        renameFile(uuid, { renameFrom, renameTo })
            .then(() => {
                pushFile({ ...file, name: values.name });
                props.onDismissed();
            })
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
                            autoFocus={true}
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
