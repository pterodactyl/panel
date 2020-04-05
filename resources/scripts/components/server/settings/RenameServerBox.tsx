import React from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Actions, useStoreActions } from 'easy-peasy';
import renameServer from '@/api/server/renameServer';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';

interface Values {
    name: string;
}

const RenameServerBox = () => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <TitledGreyBox title={'Change Server Name'} className={'relative'}>
            <SpinnerOverlay size={'normal'} visible={isSubmitting}/>
            <Form className={'mb-0'}>
                <Field
                    id={'name'}
                    name={'name'}
                    label={'Server Name'}
                    type={'text'}
                />
                <div className={'mt-6 text-right'}>
                    <button type={'submit'} className={'btn btn-sm btn-primary'}>
                        Save
                    </button>
                </div>
            </Form>
        </TitledGreyBox>
    );
};

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data!);
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = ({ name }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('settings');
        renameServer(server.uuid, name)
            .then(() => setServer({ ...server, name }))
            .catch(error => {
                console.error(error);
                addError({ key: 'settings', message: httpErrorToHuman(error) });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: server.name,
            }}
            validationSchema={object().shape({
                name: string().required().min(1),
            })}
        >
            <RenameServerBox/>
        </Formik>
    );
};
