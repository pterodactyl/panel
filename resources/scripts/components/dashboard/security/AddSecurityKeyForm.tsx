import { SecurityKey } from '@models';
import { useFlashKey } from '@/plugins/useFlash';
import { Form, Formik, FormikHelpers } from 'formik';
import { registerSecurityKey } from '@/api/account/security-keys';
import { object, string } from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import React from 'react';

interface Values {
    name: string;
}

export default ({ onKeyAdded }: { onKeyAdded: (key: SecurityKey) => void }) => {
    const { clearAndAddHttpError } = useFlashKey('security_keys');

    const submit = ({ name }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        registerSecurityKey(name)
            .then(key => {
                resetForm();
                onKeyAdded(key);
            })
            .catch(clearAndAddHttpError)
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ name: '' }}
            validationSchema={object().shape({
                name: string().required(),
            })}
        >
            {({ isSubmitting }) => (
                <Form>
                    <SpinnerOverlay visible={isSubmitting}/>
                    <Field
                        type={'text'}
                        id={'name'}
                        name={'name'}
                        label={'Name'}
                        description={'A descriptive name for this security key.'}
                    />
                    <div css={tw`flex justify-end mt-6`}>
                        <Button>Create</Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};
