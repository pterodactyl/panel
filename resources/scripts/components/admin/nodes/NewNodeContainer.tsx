import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { useNavigate } from 'react-router-dom';
import tw from 'twin.macro';
import { number, object, string } from 'yup';

import type { Values } from '@/api/admin/nodes/createNode';
import createNode from '@/api/admin/nodes/createNode';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import NodeLimitContainer from '@/components/admin/nodes/NodeLimitContainer';
import NodeListenContainer from '@/components/admin/nodes/NodeListenContainer';
import NodeSettingsContainer from '@/components/admin/nodes/NodeSettingsContainer';
import Button from '@/components/elements/Button';
import FlashMessageRender from '@/components/FlashMessageRender';
import type { ApplicationStore } from '@/state';

type Values2 = Omit<Omit<Values, 'behindProxy'>, 'public'> & { behindProxy: string; public: string };

const initialValues: Values2 = {
    name: '',
    locationId: 0,
    databaseHostId: null,
    fqdn: '',
    scheme: 'https',
    behindProxy: 'false',
    public: 'true',
    daemonBase: '/var/lib/pterodactyl/volumes',

    listenPortHTTP: 8080,
    publicPortHTTP: 8080,
    listenPortSFTP: 2022,
    publicPortSFTP: 2022,

    memory: 0,
    memoryOverallocate: 0,
    disk: 0,
    diskOverallocate: 0,
};

export default () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const submit = (values2: Values2, { setSubmitting }: FormikHelpers<Values2>) => {
        clearFlashes('node:create');

        const values: Values = {
            ...values2,
            behindProxy: values2.behindProxy === 'true',
            public: values2.public === 'true',
        };

        createNode(values)
            .then(node => navigate(`/admin/nodes/${node.id}`))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'node:create', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New Node'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>New Node</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        Add a new node to the panel.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'node:create'} />

            <Formik
                onSubmit={submit}
                initialValues={initialValues}
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
                {({ isSubmitting, isValid }) => (
                    <Form>
                        <div css={tw`flex flex-col lg:flex-row`}>
                            <div css={tw`w-full lg:w-1/2 flex flex-col mr-0 lg:mr-2`}>
                                <NodeSettingsContainer />
                            </div>

                            <div css={tw`w-full lg:w-1/2 flex flex-col ml-0 lg:ml-2 mt-4 lg:mt-0`}>
                                <div css={tw`flex w-full`}>
                                    <NodeListenContainer />
                                </div>

                                <div css={tw`flex w-full mt-4`}>
                                    <NodeLimitContainer />
                                </div>

                                <div css={tw`rounded shadow-md bg-neutral-700 mt-4 py-2 pr-6`}>
                                    <div css={tw`flex flex-row`}>
                                        <Button type={'submit'} css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                            Create
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Form>
                )}
            </Formik>
        </AdminContentBlock>
    );
};
