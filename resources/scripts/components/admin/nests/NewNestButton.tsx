import React, { useState } from 'react';
import createNest from '@/api/admin/nests/createNest';
import { httpErrorToHuman } from '@/api/http';
import { AdminContext } from '@/state/admin';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';
import { object, string } from 'yup';

interface Values {
    name: string,
    description: string,
}

const schema = object().shape({
    name: string()
        .required('A nest name must be provided.')
        .max(32, 'Nest name must not exceed 32 characters.'),
    description: string()
        .max(255, 'Nest description must not exceed 255 characters.'),
});

export default () => {
    const [ visible, setVisible ] = useState(false);
    const { addError, clearFlashes } = useFlash();

    const appendNest = AdminContext.useStoreActions(actions => actions.nests.appendNest);

    const submit = ({ name, description }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('nest:create');
        setSubmitting(true);

        createNest(name, description)
            .then(nest => {
                appendNest(nest);
                setVisible(false);
            })
            .catch(error => {
                addError({ key: 'nest:create', message: httpErrorToHuman(error) });
                setSubmitting(false);
            });
    };

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{ name: '', description: '' }}
                validationSchema={schema}
            >
                {
                    ({ isSubmitting, resetForm }) => (
                        <Modal
                            visible={visible}
                            dismissable={!isSubmitting}
                            showSpinnerOverlay={isSubmitting}
                            onDismissed={() => {
                                resetForm();
                                setVisible(false);
                            }}
                        >
                            <FlashMessageRender byKey={'nest:create'} css={tw`mb-6`}/>
                            <h2 css={tw`text-neutral-100 text-2xl mb-6`}>New Nest</h2>
                            <Form css={tw`m-0`}>
                                <Field
                                    type={'string'}
                                    id={'name'}
                                    name={'name'}
                                    label={'Name'}
                                    description={'A short name used to identify this nest.'}
                                />

                                <div css={tw`mt-6`}>
                                    <Field
                                        type={'string'}
                                        id={'description'}
                                        name={'description'}
                                        label={'Description'}
                                        description={'A description for this nest.'}
                                    />
                                </div>

                                <div css={tw`flex flex-wrap justify-end mt-6`}>
                                    <Button
                                        type={'button'}
                                        isSecondary
                                        css={tw`w-full sm:w-auto sm:mr-2`}
                                        onClick={() => setVisible(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button css={tw`w-full mt-4 sm:w-auto sm:mt-0`} type={'submit'}>
                                        Create Nest
                                    </Button>
                                </div>
                            </Form>
                        </Modal>
                    )
                }
            </Formik>

            <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`} onClick={() => setVisible(true)}>
                New Nest
            </Button>
        </>
    );
};
