import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { useState } from 'react';
import tw from 'twin.macro';
import { object, string } from 'yup';

import createLocation from '@/api/admin/locations/createLocation';
import getLocations from '@/api/admin/locations/getLocations';
import { Button } from '@/components/elements/button';
import { Size, Variant } from '@/components/elements/button/types';
import Field from '@/components/elements/Field';
import Modal from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';

interface Values {
    short: string;
    long: string;
}

const schema = object().shape({
    short: string()
        .required('A location short name must be provided.')
        .max(32, 'Location short name must not exceed 32 characters.'),
    long: string().max(255, 'Location long name must not exceed 255 characters.'),
});

export default () => {
    const [visible, setVisible] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getLocations();

    const submit = ({ short, long }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('location:create');
        setSubmitting(true);

        createLocation(short, long)
            .then(async location => {
                await mutate(data => ({ ...data!, items: data!.items.concat(location) }), false);
                setVisible(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'location:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            <Formik onSubmit={submit} initialValues={{ short: '', long: '' }} validationSchema={schema}>
                {({ isSubmitting, resetForm }) => (
                    <Modal
                        visible={visible}
                        dismissable={!isSubmitting}
                        showSpinnerOverlay={isSubmitting}
                        onDismissed={() => {
                            resetForm();
                            setVisible(false);
                        }}
                    >
                        <FlashMessageRender byKey={'location:create'} css={tw`mb-6`} />

                        <h2 css={tw`mb-6 text-2xl text-neutral-100`}>New Location</h2>

                        <Form css={tw`m-0`}>
                            <Field
                                type={'text'}
                                id={'short'}
                                name={'short'}
                                label={'Short'}
                                description={'A short name used to identify this location.'}
                                autoFocus
                            />

                            <div css={tw`mt-6`}>
                                <Field
                                    type={'text'}
                                    id={'long'}
                                    name={'long'}
                                    label={'Long'}
                                    description={'A long name for this location.'}
                                />
                            </div>

                            <div css={tw`flex flex-wrap justify-end mt-6`}>
                                <Button.Text
                                    type="button"
                                    variant={Variant.Secondary}
                                    css={tw`w-full sm:w-auto sm:mr-2`}
                                    onClick={() => setVisible(false)}
                                >
                                    Cancel
                                </Button.Text>
                                <Button css={tw`w-full mt-4 sm:w-auto sm:mt-0`} type={'submit'}>
                                    Create Location
                                </Button>
                            </div>
                        </Form>
                    </Modal>
                )}
            </Formik>

            <Button
                type="button"
                size={Size.Large}
                css={tw`h-10 px-4 py-0 whitespace-nowrap`}
                onClick={() => setVisible(true)}
            >
                New Location
            </Button>
        </>
    );
};
