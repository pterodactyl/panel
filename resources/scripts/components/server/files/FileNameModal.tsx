import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikActions } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';

type Props = RequiredModalProps & {
    onFileNamed: (name: string) => void;
};

interface Values {
    fileName: string;
}

export default ({ onFileNamed, onDismissed, ...props }: Props) => {
    const submit = (values: Values, { setSubmitting }: FormikActions<Values>) => {
        onFileNamed(values.fileName);
        setSubmitting(false);
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ fileName: '' }}
            validationSchema={object().shape({
                fileName: string().required().min(1),
            })}
        >
            {({ resetForm }) => (
                <Modal
                    onDismissed={() => {
                        resetForm();
                        onDismissed();
                    }}
                    {...props}
                >
                    <Form>
                        <Field
                            id={'fileName'}
                            name={'fileName'}
                            label={'File Name'}
                            description={'Enter the name that this file should be saved as.'}
                        />
                        <div className={'mt-6 text-right'}>
                            <button className={'btn btn-primary btn-sm'}>
                                Create File
                            </button>
                        </div>
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};
