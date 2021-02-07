import LocationSelect from '@/components/admin/nodes/LocationSelect';
import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object, string } from 'yup';
import updateNode from '@/api/admin/nodes/updateNode';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import { Context } from '@/components/admin/nodes/NodeEditContainer';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';

interface Values {
    public: boolean;
    name: string;
    description: string;
    locationId: number;
    fqdn: string;
    listenPort: number;
    publicPort: number;
    listenPortSFTP: number;
    publicPortSFTP: number;
}

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const node = Context.useStoreState(state => state.node);
    const setNode = Context.useStoreActions(actions => actions.setNode);

    if (node === undefined) {
        return (
            <></>
        );
    }

    const submit = ({ name, description }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('database');

        updateNode(node.id, name, description)
            .then(() => setNode({ ...node, name, description }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'node', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                public: node.public,
                name: node.name,
                description: node.description || '',
                locationId: node.locationId,
                fqdn: node.fqdn,
                listenPort: node.daemonListen,
                publicPort: node.daemonListen,
                listenPortSFTP: node.daemonSftp,
                publicPortSFTP: node.daemonSftp,
            }}
            validationSchema={object().shape({
                name: string().required().max(191),
                description: string().max(255),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Settings'} css={tw`relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div css={tw`mb-6`}>
                                    <Field
                                        id={'name'}
                                        name={'name'}
                                        label={'Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mb-6`}>
                                    <Field
                                        id={'description'}
                                        name={'description'}
                                        label={'Description'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mb-6`}>
                                    <LocationSelect defaultLocation={{ id: 1, short: 'local', long: '', createdAt: new Date(), updatedAt: new Date() }}/>
                                </div>

                                <div css={tw`mb-6`}>
                                    <Field
                                        id={'fqdn'}
                                        name={'fqdn'}
                                        label={'FQDN'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-row mb-6`}>
                                    <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mb-6 md:mb-0`}>
                                        <Field
                                            id={'listenPort'}
                                            name={'listenPort'}
                                            label={'Listen Port'}
                                            type={'number'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mb-6 md:mb-0`}>
                                        <Field
                                            id={'publicPort'}
                                            name={'publicPort'}
                                            label={'Public Port'}
                                            type={'number'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-row mb-6`}>
                                    <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mb-6 md:mb-0`}>
                                        <Field
                                            id={'listenPortSFTP'}
                                            name={'listenPortSFTP'}
                                            label={'SFTP Listen Port'}
                                            type={'number'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mb-6 md:mb-0`}>
                                        <Field
                                            id={'publicPortSFTP'}
                                            name={'publicPortSFTP'}
                                            label={'SFTP Public Port'}
                                            type={'number'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`w-full flex flex-row items-center`}>
                                    <div css={tw`flex ml-auto`}>
                                        <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </Form>
                        </AdminBox>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};
