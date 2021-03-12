import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object, string } from 'yup';
import updateNode from '@/api/admin/nodes/updateNode';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Field as FormikField, Form, Formik, FormikHelpers } from 'formik';
import { Context } from '@/components/admin/nodes/NodeEditContainer';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import LocationSelect from '@/components/admin/nodes/LocationSelect';
import DatabaseSelect from '@/components/admin/nodes/DatabaseSelect';
import Label from '@/components/elements/Label';

interface Values {
    public: boolean;
    name: string;
    description: string;
    locationId: number;
    databaseHostId: number | null;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
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

    const submit = ({ name, description, locationId, databaseHostId, fqdn, scheme, behindProxy }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('node');

        updateNode(node.id, { name, description, locationId, databaseHostId, fqdn, scheme, behindProxy })
            .then(() => setNode({ ...node, name, description, locationId, fqdn, scheme, behindProxy }))
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
                databaseHostId: node.databaseHostId,
                fqdn: node.fqdn,
                scheme: node.scheme,
                behindProxy: node.behindProxy,
            }}
            validationSchema={object().shape({
                name: string().required().max(191),
                description: string().max(255),
            })}
        >
            {
                ({ isSubmitting }) => (
                    <React.Fragment>
                        <AdminBox title={'Settings'} css={tw`w-full relative`}>
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
                                    <LocationSelect selected={node?.relations.location || null}/>
                                </div>

                                <div css={tw`mb-6`}>
                                    <DatabaseSelect selected={node?.relations.databaseHost || null}/>
                                </div>

                                <div css={tw`mb-6`}>
                                    <Field
                                        id={'fqdn'}
                                        name={'fqdn'}
                                        label={'FQDN'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mt-6`}>
                                    <Label htmlFor={'scheme'}>SSL</Label>

                                    <div>
                                        <label css={tw`inline-flex items-center mr-2`}>
                                            <FormikField
                                                name={'scheme'}
                                                type={'radio'}
                                                value={'https'}
                                            />
                                            <span css={tw`text-neutral-300 ml-2`}>Enabled</span>
                                        </label>

                                        <label css={tw`inline-flex items-center ml-2`}>
                                            <FormikField
                                                name={'scheme'}
                                                type={'radio'}
                                                value={'http'}
                                            />
                                            <span css={tw`text-neutral-300 ml-2`}>Disabled</span>
                                        </label>
                                    </div>
                                </div>

                                <div css={tw`mt-6`}>
                                    <Label htmlFor={'behindProxy'}>Behind Proxy</Label>

                                    <div>
                                        <label css={tw`inline-flex items-center mr-2`}>
                                            <FormikField
                                                name={'behindProxy'}
                                                type={'radio'}
                                                value={false}
                                            />
                                            <span css={tw`text-neutral-300 ml-2`}>No</span>
                                        </label>

                                        <label css={tw`inline-flex items-center ml-2`}>
                                            <FormikField
                                                name={'behindProxy'}
                                                type={'radio'}
                                                value
                                            />
                                            <span css={tw`text-neutral-300 ml-2`}>Yes</span>
                                        </label>
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
