import { join } from 'path';
import tw from 'twin.macro';
import { object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import Modal from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import React, { useEffect, useState } from 'react';
import { WithClassname } from '@/components/types';
import { Form, Formik, FormikHelpers } from 'formik';
import { Button } from '@/components/elements/button/index';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { FileObject } from '@/api/server/files/loadDirectory';
import createDirectory from '@/api/server/files/createDirectory';
import FlashMessageRender from '@/components/FlashMessageRender';

interface Values {
    directoryName: string;
}

const schema = object().shape({
    directoryName: string().required('A valid directory name must be provided.'),
});

const generateDirectoryData = (name: string): FileObject => ({
    key: `dir_${name.split('/', 1)[0] ?? name}`,
    name: name.replace(/^(\/*)/, '').split('/', 1)[0] ?? name,
    mode: 'drwxr-xr-x',
    modeBits: '0755',
    size: 0,
    isFile: false,
    isSymlink: false,
    mimetype: '',
    createdAt: new Date(),
    modifiedAt: new Date(),
    isArchiveType: () => false,
    isEditable: () => false,
});

export default ({ className }: WithClassname) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ visible, setVisible ] = useState(false);

    const { mutate } = useFileManagerSwr();
    const directory = ServerContext.useStoreState(state => state.files.directory);

    useEffect(() => {
        if (!visible) return;

        return () => {
            clearFlashes('files:directory-modal');
        };
    }, [ visible ]);

    const submit = ({ directoryName }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        createDirectory(uuid, directory, directoryName)
            .then(() => mutate(data => [ ...data, generateDirectoryData(directoryName) ], false))
            .then(() => setVisible(false))
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files:directory-modal', error });
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
                        <FlashMessageRender key={'files:directory-modal'}/>
                        <Form css={tw`m-0`}>
                            <Field
                                autoFocus
                                id={'directoryName'}
                                name={'directoryName'}
                                label={'Directory Name'}
                            />
                            <p css={tw`text-xs mt-2 text-neutral-400 break-all`}>
                                <span css={tw`text-neutral-200`}>This directory will be created as</span>
                                &nbsp;/home/container/
                                <span css={tw`text-cyan-200`}>
                                    {join(directory, values.directoryName).replace(/^(\.\.\/|\/)+/, '')}
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
            <Button variant={Button.Variants.Secondary} onClick={() => setVisible(true)} className={className}>
                Create Directory
            </Button>
        </>
    );
};
