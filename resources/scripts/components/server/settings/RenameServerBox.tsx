import React from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Form, FormikProps, withFormik } from 'formik';
import { Server } from '@/api/server/getServer';
import { ActionCreator } from 'easy-peasy';
import renameServer from '@/api/server/renameServer';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface OwnProps {
    server: Server;
    setServer: ActionCreator<Server>;
}

interface Values {
    name: string;
}

const RenameServerBox = ({ isSubmitting, ...props }: OwnProps & FormikProps<Values>) => (
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

const EnhancedForm = withFormik<OwnProps, Values>({
    displayName: 'RenameServerBoxForm',

    mapPropsToValues: props => ({
        name: props.server.name,
    }),

    validationSchema: () => object().shape({
        name: string().required().min(1),
    }),

    handleSubmit: (values, { props, setSubmitting }) => {
        renameServer(props.server.uuid, values.name)
            .then(() => props.setServer({ ...props.server, name: values.name }))
            .catch(error => {
                console.error(error);
            })
            .then(() => setSubmitting(false));
    },
})(RenameServerBox);

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data!);
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer);

    return <EnhancedForm server={server} setServer={setServer}/>;
};
