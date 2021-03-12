import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object } from 'yup';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import { Context } from '@/components/admin/nodes/NodeEditContainer';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import updateNode from '@/api/admin/nodes/updateNode';

interface Values {
    listenPortHTTP: number;
    publicPortHTTP: number;
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

    const submit = ({ listenPortHTTP, publicPortHTTP, listenPortSFTP, publicPortSFTP }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('node');

        updateNode(node.id, { listenPortHTTP, publicPortHTTP, listenPortSFTP, publicPortSFTP })
            .then(() => setNode({ ...node, listenPortHTTP, publicPortHTTP, listenPortSFTP, publicPortSFTP }))
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
                listenPortHTTP: node.listenPortHTTP,
                publicPortHTTP: node.publicPortHTTP,
                listenPortSFTP: node.listenPortSFTP,
                publicPortSFTP: node.publicPortSFTP,
            }}
            validationSchema={object().shape({
            })}
        >
            {
                ({ isSubmitting }) => (
                    <React.Fragment>
                        <AdminBox title={'Listen'} css={tw`w-full relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                                        <Field
                                            id={'listenPortHTTP'}
                                            name={'listenPortHTTP'}
                                            label={'HTTP Listen Port'}
                                            type={'number'}
                                        />
                                    </div>

                                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                                        <Field
                                            id={'publicPortHTTP'}
                                            name={'publicPortHTTP'}
                                            label={'HTTP Public Port'}
                                            type={'number'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                                        <Field
                                            id={'listenPortSFTP'}
                                            name={'listenPortSFTP'}
                                            label={'SFTP Listen Port'}
                                            type={'number'}
                                        />
                                    </div>

                                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                                        <Field
                                            id={'publicPortSFTP'}
                                            name={'publicPortSFTP'}
                                            label={'SFTP Public Port'}
                                            type={'number'}
                                        />
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
