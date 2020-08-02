import React, { useState } from 'react';
import Modal from '@/components/elements/Modal';
import { ServerContext } from '@/state/server';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { join } from 'path';
import { object, string } from 'yup';
import createDirectory from '@/api/server/files/createDirectory';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { mutate } from 'swr';
import useServer from '@/plugins/useServer';
import { FileObject } from '@/api/server/files/loadDirectory';
import { useLocation } from 'react-router';
import useFlash from '@/plugins/useFlash';

interface Values {
    directoryName: string;
}

const schema = object().shape({
    directoryName: string().required('A valid directory name must be provided.'),
});

const generateDirectoryData = (name: string): FileObject => ({
    key: `dir_${name}`,
    name: name,
    mode: '0644',
    size: 0,
    isFile: false,
    isEditable: false,
    isSymlink: false,
    mimetype: '',
    createdAt: new Date(),
    modifiedAt: new Date(),
    isArchiveType: () => false,
});

export default () => {
    const { uuid } = useServer();
    const { hash } = useLocation();
    const { clearAndAddHttpError } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const submit = ({ directoryName }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        createDirectory(uuid, directory, directoryName)
            .then(() => {
                mutate(
                    `${uuid}:files:${hash}`,
                    (data: FileObject[]) => [ ...data, generateDirectoryData(directoryName) ],
                );
                setVisible(false);
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files', error });
            });
    };

    return (
        <>
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
                        <Form css={tw`m-0`}>
                            <Field
                                id={'directoryName'}
                                name={'directoryName'}
                                label={'Directory Name'}
                            />
                            <p css={tw`text-xs mt-2 text-neutral-400`}>
                                <span css={tw`text-neutral-200`}>This directory will be created as</span>
                                &nbsp;/home/container/
                                <span css={tw`text-cyan-200`}>
                                    {decodeURIComponent(
                                        join(directory, values.directoryName).replace(/^(\.\.\/|\/)+/, ''),
                                    )}
                                </span>
                            </p>
                            <div css={tw`flex justify-end`}>
                                <Button css={tw`mt-8`}>
                                    Create Directory
                                </Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>
            <Button isSecondary css={tw`mr-2`} onClick={() => setVisible(true)}>
                Create Directory
            </Button>
        </>
    );
};
