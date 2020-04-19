import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import useFlash from '@/plugins/useFlash';
import useServer from '@/plugins/useServer';
import createServerBackup from '@/api/server/backups/createServerBackup';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';

interface Values {
    name: string;
    ignored: string;
}

const ModalContent = ({ ...props }: RequiredModalProps) => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form className={'pb-6'}>
                <FlashMessageRender byKey={'backups:create'} className={'mb-4'}/>
                <h3 className={'mb-6'}>Create server backup</h3>
                <div className={'mb-6'}>
                    <Field
                        name={'name'}
                        label={'Backup name'}
                        description={'If provided, the name that should be used to reference this backup.'}
                    />
                </div>
                <div className={'mb-6'}>
                    <FormikFieldWrapper
                        name={'ignored'}
                        label={'Ignored Files & Directories'}
                        description={`
                            Enter the files or folders to ignore while generating this backup. Leave blank to use
                            the contents of the .pteroignore file in the root of the server directory if present.
                            Wildcard matching of files and folders is supported in addition to negating a rule by
                            prefixing the path with an exclamation point.
                        `}
                    >
                        <FormikField
                            name={'ignored'}
                            component={'textarea'}
                            className={'input-dark h-32'}
                        />
                    </FormikFieldWrapper>
                </div>
                <div className={'flex justify-end'}>
                    <button
                        type={'submit'}
                        className={'btn btn-primary btn-sm'}
                    >
                        Start backup
                    </button>
                </div>
            </Form>
        </Modal>
    );
};

export default () => {
    const { uuid } = useServer();
    const { addError, clearFlashes } = useFlash();
    const [ visible, setVisible ] = useState(false);

    const appendBackup = ServerContext.useStoreActions(actions => actions.backups.appendBackup);

    useEffect(() => {
        clearFlashes('backups:create');
    }, [ visible ]);

    const submit = ({ name, ignored }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('backups:create');
        createServerBackup(uuid, name, ignored)
            .then(backup => {
                appendBackup(backup);
                setVisible(false);
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'backups:create', message: httpErrorToHuman(error) });
                setSubmitting(false);
            });
    };

    return (
        <>
            {visible &&
            <Formik
                onSubmit={submit}
                initialValues={{ name: '', ignored: '' }}
                validationSchema={object().shape({
                    name: string().max(255),
                    ignored: string(),
                })}
            >
                <ModalContent
                    appear={true}
                    visible={visible}
                    onDismissed={() => setVisible(false)}
                />
            </Formik>
            }
            <button
                className={'btn btn-primary btn-sm'}
                onClick={() => setVisible(true)}
            >
                Create backup
            </button>
        </>
    );
};
