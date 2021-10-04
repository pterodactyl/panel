import { Form, Formik, FormikHelpers } from 'formik';
import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { object, string } from 'yup';
import pullFile from '@/api/server/files/pullFile';
import { WithClassname } from '@/components/types';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import Modal from '@/components/elements/Modal';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import { FileObject } from '@/api/server/files/loadDirectory';
import FlashMessageRender from '@/components/FlashMessageRender';
import { join } from 'path';

interface Values {
    url: string;
}

const generateFileData = (name: string): FileObject => ({
    key: `file_${name.split('/', 1)[0] ?? name}`,
    name: name,
    mode: 'rw-rw-rw-',
    modeBits: '0644',
    size: 0,
    isFile: true,
    isSymlink: false,
    mimetype: '',
    createdAt: new Date(),
    modifiedAt: new Date(),
    isArchiveType: () => false,
    isEditable: () => false,
});

const parseURL = (url: string): string => {
    try {
        return new URL(url).pathname.split('/').pop() || '';
    } catch (e) {
        return '';
    }
};

export default ({ className }: WithClassname) => {
    const [ visible, setVisible ] = useState(false);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, mutate } = useFileManagerSwr();

    useEffect(() => {
        if (!visible) return;

        return () => {
            clearFlashes('files:pull-modal');
        };
    }, [ visible ]);

    const submit = ({ url }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        pullFile(uuid, directory, url)
            .then(() => mutate(data => [ ...data!, generateFileData(parseURL(url)) ], false))
            .then(() => setVisible(false))
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files:pull-modal', error });
            });
    };

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{ url: '' }}
                validationSchema={object().shape({
                    url: string()
                        .required()
                        .url()
                        .test('unique', 'File or directory with that name already exists.', v => {
                            return v !== undefined &&
                                data !== undefined &&
                                data.filter(f => f.name.toLowerCase() === v.toLowerCase()).length < 1;
                        }),
                })}
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
                        <FlashMessageRender key={'files:pull-modal'}/>
                        <Form css={tw`m-0`}>
                            <Field
                                type={'text'}
                                id={'url'}
                                name={'url'}
                                label={'URL'}
                                autoFocus
                            />
                            <p css={tw`text-xs mt-2 text-neutral-400 break-all`}>
                                <span css={tw`text-neutral-200`}>This file will be downloaded to</span>
                                &nbsp;/home/container/
                                <span css={tw`text-cyan-200`}>
                                    {values.url !== '' ? join(directory, parseURL(values.url)).substr(1) : ''}
                                </span>
                            </p>
                            <div css={tw`flex justify-end`}>
                                <Button type={'submit'} css={tw`mt-8`}>
                                    Pull File
                                </Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>
            <Button onClick={() => setVisible(true)} className={className} isSecondary>
                Pull Remote File
            </Button>
        </>
    );
};
