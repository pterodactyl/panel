import updateNode from '@/api/admin/nodes/updateNode';
import NodeDeleteButton from '@/components/admin/nodes/NodeDeleteButton';
import NodeLimitContainer from '@/components/admin/nodes/NodeLimitContainer';
import NodeListenContainer from '@/components/admin/nodes/NodeListenContainer';
import { Context } from '@/components/admin/nodes/NodeRouter';
import NodeSettingsContainer from '@/components/admin/nodes/NodeSettingsContainer';
import Button from '@/components/elements/Button';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import React from 'react';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import { number, object, string } from 'yup';

interface Values {
    name: string;
    locationId: number;
    databaseHostId: number | null;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    public: boolean;
    daemonBase: string; // This value cannot be updated once a node has been created.

    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;

    listenPortHTTP: number;
    publicPortHTTP: number;
    listenPortSFTP: number;
    publicPortSFTP: number;
}

export default () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const node = Context.useStoreState(state => state.node);
    const setNode = Context.useStoreActions(actions => actions.setNode);

    if (node === undefined) {
        return (
            <></>
        );
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
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
                daemonBase: node.daemonBase,

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
                                <NodeSettingsContainer node={node}/>
                            </div>

                            <div css={tw`w-full lg:w-1/2 flex flex-col ml-0 lg:ml-2 mt-4 lg:mt-0`}>
                                <div css={tw`flex w-full`}>
                                    <NodeListenContainer/>
                                </div>

                                <div css={tw`flex w-full mt-4`}>
                                    <NodeLimitContainer/>
                                </div>

                                <div css={tw`rounded shadow-md bg-neutral-700 mt-4 py-2 px-6`}>
                                    <div css={tw`flex flex-row`}>
                                        <NodeDeleteButton
                                            nodeId={node?.id}
                                            onDeleted={() => history.push('/admin/nodes')}
                                        />
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
