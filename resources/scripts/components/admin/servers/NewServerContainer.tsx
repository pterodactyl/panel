import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import type { FormikHelpers } from 'formik';
import { Form, Formik, useFormikContext } from 'formik';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';

import type { Egg } from '@/api/admin/egg';
import type { CreateServerRequest } from '@/api/admin/servers/createServer';
import createServer from '@/api/admin/servers/createServer';
import type { Allocation, Node } from '@/api/admin/node';
import { getAllocations } from '@/api/admin/node';
import AdminBox from '@/components/admin/AdminBox';
import NodeSelect from '@/components/admin/servers/NodeSelect';
import {
    ServerImageContainer,
    ServerServiceContainer,
    ServerVariableContainer,
} from '@/components/admin/servers/ServerStartupContainer';
import BaseSettingsBox from '@/components/admin/servers/settings/BaseSettingsBox';
import FeatureLimitsBox from '@/components/admin/servers/settings/FeatureLimitsBox';
import ServerResourceBox from '@/components/admin/servers/settings/ServerResourceBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

function InternalForm() {
    const {
        isSubmitting,
        isValid,
        setFieldValue,
        values: { environment },
    } = useFormikContext<CreateServerRequest>();

    const [egg, setEgg] = useState<Egg | null>(null);
    const [node, setNode] = useState<Node | null>(null);
    const [allocations, setAllocations] = useState<Allocation[] | null>(null);

    useEffect(() => {
        if (egg === null) {
            return;
        }

        setFieldValue('eggId', egg.id);
        setFieldValue('startup', '');
        setFieldValue('image', Object.values(egg.dockerImages)[0] ?? '');
    }, [egg]);

    useEffect(() => {
        if (node === null) {
            return;
        }

        // server_id: 0 filters out assigned allocations
        getAllocations(node.id, { filters: { server_id: '0' } }).then(setAllocations);
    }, [node]);

    return (
        <Form>
            <div css={tw`grid grid-cols-2 gap-y-6 gap-x-8 mb-16`}>
                <div css={tw`grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1`}>
                    <BaseSettingsBox>
                        <NodeSelect node={node} setNode={setNode} />
                        <div css={tw`xl:col-span-2 bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                            <FormikSwitch
                                name={'startOnCompletion'}
                                label={'Start after installation'}
                                description={'Should the server be automatically started after it has been installed?'}
                            />
                        </div>
                    </BaseSettingsBox>
                    <FeatureLimitsBox />
                    <ServerServiceContainer egg={egg} setEgg={setEgg} nestId={0} />
                </div>
                <div css={tw`grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1`}>
                    <AdminBox icon={faNetworkWired} title={'Networking'} isLoading={isSubmitting}>
                        <div css={tw`grid grid-cols-1 gap-4 lg:gap-6`}>
                            <div>
                                <Label htmlFor={'allocation.default'}>Primary Allocation</Label>
                                <Select
                                    id={'allocation.default'}
                                    name={'allocation.default'}
                                    disabled={node === null}
                                    onChange={e => setFieldValue('allocation.default', Number(e.currentTarget.value))}
                                >
                                    {node === null ? (
                                        <option value="">Select a node...</option>
                                    ) : (
                                        <option value="">Select an allocation...</option>
                                    )}
                                    {allocations?.map(a => (
                                        <option key={a.id} value={a.id.toString()}>
                                            {a.getDisplayText()}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            {/*<div>*/}
                            {/*    /!* TODO: Multi-select *!/*/}
                            {/*    <Label htmlFor={'allocation.additional'}>Additional Allocations</Label>*/}
                            {/*    <Select id={'allocation.additional'} name={'allocation.additional'} disabled={node === null}>*/}
                            {/*        {node === null ? <option value="">Select a node...</option> : <option value="">Select additional allocations...</option>}*/}
                            {/*        {allocations?.map(a => <option key={a.id} value={a.id.toString()}>{a.getDisplayText()}</option>)}*/}
                            {/*    </Select>*/}
                            {/*</div>*/}
                        </div>
                    </AdminBox>
                    <ServerResourceBox />
                    <ServerImageContainer />
                </div>

                <AdminBox title={'Startup Command'} css={tw`relative w-full col-span-2`}>
                    <SpinnerOverlay visible={isSubmitting} />

                    <Field
                        id={'startup'}
                        name={'startup'}
                        label={'Startup Command'}
                        type={'text'}
                        description={
                            "Edit your server's startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}."
                        }
                        placeholder={egg?.startup || ''}
                    />
                </AdminBox>

                <div css={tw`col-span-2 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8`}>
                    {/* This ensures that no variables are rendered unless the environment has a value for the variable. */}
                    {egg?.relationships.variables
                        ?.filter(v => Object.keys(environment).find(e => e === v.environmentVariable) !== undefined)
                        .map((v, i) => (
                            <ServerVariableContainer key={i} variable={v} />
                        ))}
                </div>

                <div css={tw`bg-neutral-700 rounded shadow-md px-4 py-3 col-span-2`}>
                    <div css={tw`flex flex-row`}>
                        <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                            Create Server
                        </Button>
                    </div>
                </div>
            </div>
        </Form>
    );
}

export default () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = (r: CreateServerRequest, { setSubmitting }: FormikHelpers<CreateServerRequest>) => {
        clearFlashes('server:create');

        createServer(r)
            .then(s => navigate(`/admin/servers/${s.id}`))
            .catch(error => clearAndAddHttpError({ key: 'server:create', error }))
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New Server'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>New Server</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        Add a new server to the panel.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'server:create'} css={tw`mb-4`} />

            <Formik
                onSubmit={submit}
                initialValues={
                    {
                        externalId: '',
                        name: '',
                        description: '',
                        ownerId: 0,
                        nodeId: 0,
                        limits: {
                            memory: 1024,
                            swap: 0,
                            disk: 4096,
                            io: 500,
                            cpu: 0,
                            threads: '',
                            oomKiller: true,
                        },
                        featureLimits: {
                            allocations: 1,
                            backups: 0,
                            databases: 0,
                        },
                        allocation: {
                            default: 0,
                            additional: [] as number[],
                        },
                        startup: '',
                        environment: [],
                        eggId: 0,
                        image: '',
                        skipScripts: false,
                        startOnCompletion: true,
                    } as CreateServerRequest
                }
                validationSchema={object().shape({})}
            >
                <InternalForm />
            </Formik>
        </AdminContentBlock>
    );
};
