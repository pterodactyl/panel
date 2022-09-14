import React from 'react';
import tw from 'twin.macro';
import { object, string } from 'yup';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import Field from '@/components/elements/Field';
import { Actions, useStoreActions } from 'easy-peasy';
import { Button } from '@/components/elements/button/index';
import changeBackground from '@/api/server/changeBackground';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';

interface Values {
    bg: string;
}

const ChangeBackgroundBox = () => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <TitledGreyBox title={'Change Server Background'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting} />
            <Form css={tw`mb-0`}>
                <Field id={'bg'} name={'bg'} label={'Server Background'} type={'text'} />
                <div css={tw`mt-6 text-right`}>
                    <Button type={'submit'}>Save</Button>
                </div>
            </Form>
        </TitledGreyBox>
    );
};

export default () => {
    const server = ServerContext.useStoreState((state) => state.server.data!);
    const setServer = ServerContext.useStoreActions((actions) => actions.server.setServer);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = ({ bg }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('settings');
        changeBackground(server.uuid, bg)
            .then(() => setServer({ ...server, bg }))
            .catch((error) => {
                console.error(error);
                addError({ key: 'settings', message: httpErrorToHuman(error) });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                bg: server.bg,
            }}
            validationSchema={object().shape({
                bg: string().nullable().url(),
            })}
        >
            <ChangeBackgroundBox />
        </Formik>
    );
};
