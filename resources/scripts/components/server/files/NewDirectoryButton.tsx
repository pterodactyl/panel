import React, { useState } from 'react';
import Modal from '@/components/elements/Modal';
import { ServerContext } from '@/state/server';
import { Form, Formik, FormikActions } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'path';
import { object, string } from 'yup';
import createDirectory from '@/api/server/files/createDirectory';
import v4 from 'uuid/v4';

interface Values {
    directoryName: string;
}

const schema = object().shape({
    directoryName: string().required('A valid directory name must be provided.'),
});

export default () => {
    const [ visible, setVisible ] = useState(false);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const directory = ServerContext.useStoreState(state => state.files.directory);
    const pushFile = ServerContext.useStoreActions(actions => actions.files.pushFile);

    const submit = (values: Values, { setSubmitting }: FormikActions<Values>) => {
        createDirectory(uuid, directory, values.directoryName)
            .then(() => {
                pushFile({
                    uuid: v4(),
                    name: values.directoryName,
                    mode: '0644',
                    size: 0,
                    isFile: false,
                    isEditable: false,
                    isSymlink: false,
                    mimetype: '',
                    createdAt: new Date(),
                    modifiedAt: new Date(),
                });
                setVisible(false);
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
            });
    };

    return (
        <React.Fragment>
            <Formik
                onSubmit={submit}
                validationSchema={schema}
                initialValues={{ directoryName: '' }}
            >
                {({ resetForm, isSubmitting, values }) => (
                    <Modal
                        visible={visible}
                        dismissable={!isSubmitting}
                        showSpinnerOverlay={isSubmitting}
                        onDismissed={() => {
                            setVisible(false);
                            resetForm();
                        }}
                    >
                        <Form className={'m-0'}>
                            <Field
                                id={'directoryName'}
                                name={'directoryName'}
                                label={'Directory Name'}
                            />
                            <p className={'text-xs mt-2 text-neutral-400'}>
                                <span className={'text-neutral-200'}>This directory will be created as</span>
                                &nbsp;/home/container/<span className={'text-cyan-200'}>{join(directory, values.directoryName).replace(/^(\.\.\/|\/)+/, '')}</span>
                            </p>
                            <div className={'flex justify-end'}>
                                <button className={'btn btn-sm btn-primary mt-8'}>
                                    Create Directory
                                </button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>
            <button className={'btn btn-sm btn-secondary mr-2'} onClick={() => setVisible(true)}>
                Create Directory
            </button>
        </React.Fragment>
    );
};
