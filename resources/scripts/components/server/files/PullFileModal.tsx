import tw from 'twin.macro';
import { join } from 'path';
import { object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import Code from '@/components/elements/Code';
import { ServerContext } from '@/state/server';
import Field from '@/components/elements/Field';
import Portal from '@/components/elements/Portal';
import pullFile from '@/api/server/files/pullFile';
import { WithClassname } from '@/components/types';
import React, { useEffect, useState } from 'react';
import { Form, Formik, FormikHelpers } from 'formik';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { FileObject } from '@/api/server/files/loadDirectory';
import FlashMessageRender from '@/components/FlashMessageRender';

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

export default ({ className }: WithClassname) => {
    const [visible, setVisible] = useState(false);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const directory = ServerContext.useStoreState((state) => state.files.directory);

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data, mutate } = useFileManagerSwr();

    useEffect(() => {
        if (!visible) return;

        return () => {
            clearFlashes('files:pull-modal');
        };
    }, [visible]);

    const submit = ({ url }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        pullFile(uuid, directory, url)
            .then(() =>
                mutate((data) => [...data!, generateFileData(new URL(url).pathname.split('/').pop() || '')], false)
            )
            .then(() => setVisible(false))
            .catch((error) => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'files:pull-modal', error });
            });
    };

    return (
        <>
            <Portal>
                <Formik
                    onSubmit={submit}
                    initialValues={{ url: '' }}
                    validationSchema={object().shape({
                        url: string()
                            .required()
                            .url()
                            .test('unique', 'File or directory with that name already exists.', (v) => {
                                return (
                                    v !== undefined &&
                                    data !== undefined &&
                                    data.filter((f) => f.name.toLowerCase() === v.toLowerCase()).length < 1
                                );
                            }),
                    })}
                >
                    {({ resetForm, submitForm, isSubmitting: _, values }) => (
                        <Dialog
                            title={'Pull Remote File'}
                            open={visible}
                            onClose={() => {
                                setVisible(false);
                                resetForm();
                            }}
                        >
                            <FlashMessageRender key={'files:pull-modal'} />
                            <Form css={tw`m-0`}>
                                <Field type={'text'} id={'url'} name={'url'} label={'URL'} autoFocus />
                                <p css={tw`mt-2 text-sm md:text-base break-all`}>
                                    <span css={tw`text-neutral-200`}>This file will be downloaded to&nbsp;</span>
                                    <Code>
                                        /home/container/
                                        <span css={tw`text-cyan-200`}>
                                            {join(
                                                directory,
                                                values.url.split('/')[values.url.split('/').length - 1]
                                            ).replace(/^(\.\.\/|\/)+/, '')}
                                        </span>
                                    </Code>
                                </p>
                            </Form>
                            <Dialog.Footer>
                                <Button.Text
                                    className={'w-full sm:w-auto'}
                                    onClick={() => {
                                        setVisible(false);
                                        resetForm();
                                    }}
                                >
                                    Cancel
                                </Button.Text>
                                <Button className={'w-full sm:w-auto'} onClick={submitForm}>
                                    Pull File
                                </Button>
                            </Dialog.Footer>
                        </Dialog>
                    )}
                </Formik>
            </Portal>
            <Button.Text onClick={() => setVisible(true)} className={className}>
                Pull Remote File
            </Button.Text>
        </>
    );
};
