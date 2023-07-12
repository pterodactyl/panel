import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { join } from 'pathe';
import tw from 'twin.macro';
import { object, string } from 'yup';

import { Button } from '@/components/elements/button';
import Field from '@/components/elements/Field';
import type { RequiredModalProps } from '@/components/elements/Modal';
import Modal from '@/components/elements/Modal';
import { ServerContext } from '@/state/server';

type Props = RequiredModalProps & {
    onFileNamed: (name: string) => void;
};

interface Values {
    fileName: string;
}

export default ({ onFileNamed, onDismissed, ...props }: Props) => {
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        onFileNamed(join(directory, values.fileName).replace(/^\//, ''));
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
                            autoFocus
                        />
                        <div css={tw`mt-6 text-right`}>
                            <Button>Create File</Button>
                        </div>
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};
