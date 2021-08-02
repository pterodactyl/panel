import Button from '@/components/elements/Button';
import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { number, object, string } from 'yup';
import updateNode from '@/api/admin/nodes/updateNode';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Context } from '@/components/admin/nodes/NodeRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import LocationSelect from '@/components/admin/nodes/LocationSelect';
import DatabaseSelect from '@/components/admin/nodes/DatabaseSelect';
import Label from '@/components/elements/Label';
import NodeLimitContainer from '@/components/admin/nodes/NodeLimitContainer';
import NodeListenContainer from '@/components/admin/nodes/NodeListenContainer';

interface Values {
    name: string;
    locationId: number;
    databaseHostId: number | null;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    public: boolean;

    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;

    listenPortHTTP: number;
    publicPortHTTP: number;
    listenPortSFTP: number;
    publicPortSFTP: number;
}

const NodeSettingsContainer = () => {
    const { isSubmitting } = useFormikContext();

    const node = Context.useStoreState(state => state.node);

    if (node === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Settings'} css={tw`w-full relative`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`mb-6`}>
                <Field
                    id={'name'}
                    name={'name'}
                    label={'Name'}
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

            <div css={tw`mt-6`}>
                <Label htmlFor={'public'}>Automatic Allocation</Label>

                <div>
                    <label css={tw`inline-flex items-center mr-2`}>
                        <FormikField
                            name={'public'}
                            type={'radio'}
                            value={false}
                        />
                        <span css={tw`text-neutral-300 ml-2`}>Disabled</span>
                    </label>

                    <label css={tw`inline-flex items-center ml-2`}>
                        <FormikField
                            name={'public'}
                            type={'radio'}
                            value
                        />
                        <span css={tw`text-neutral-300 ml-2`}>Enabled</span>
                    </label>
                </div>
            </div>
        </AdminBox>
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const node = Context.useStoreState(state => state.node);
    const setNode = Context.useStoreActions(actions => actions.setNode);

    if (node === undefined) {
        return (
            <></>
        );
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        console.log('submit!');
        clearFlashes('node');

        updateNode(node.id, values)
            .then(() => setNode({ ...node, ...values }))
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
                name: node.name,
                locationId: node.locationId,
                databaseHostId: node.databaseHostId,
                fqdn: node.fqdn,
                scheme: node.scheme,
                behindProxy: node.behindProxy,
                public: node.public,

                listenPortHTTP: node.listenPortHTTP,
                publicPortHTTP: node.publicPortHTTP,
                listenPortSFTP: node.listenPortSFTP,
                publicPortSFTP: node.publicPortSFTP,

                memory: node.memory,
                memoryOverallocate: node.memoryOverallocate,
                disk: node.disk,
                diskOverallocate: node.diskOverallocate,
            }}
            validationSchema={object().shape({
                name: string().required().max(191),

                listenPortHTTP: number().required(),
                publicPortHTTP: number().required(),
                listenPortSFTP: number().required(),
                publicPortSFTP: number().required(),

                memory: number().required(),
                memoryOverallocate: number().required(),
                disk: number().required(),
                diskOverallocate: number().required(),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <Form>
                        <div css={tw`flex flex-col lg:flex-row`}>
                            <div css={tw`w-full lg:w-1/2 flex flex-col mr-0 lg:mr-2`}>
                                <NodeSettingsContainer/>
                            </div>

                            <div css={tw`w-full lg:w-1/2 flex flex-col ml-0 lg:ml-2 mt-4 lg:mt-0`}>
                                <div css={tw`flex w-full`}>
                                    <NodeListenContainer/>
                                </div>

                                <div css={tw`flex w-full mt-4`}>
                                    <NodeLimitContainer/>
                                </div>

                                <div css={tw`rounded shadow-md bg-neutral-700 mt-4 py-2 pr-6`}>
                                    <div css={tw`flex flex-row`}>
                                        <Button type={'submit'} css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                            Save Changes
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Form>
                )
            }
        </Formik>
    );
};
