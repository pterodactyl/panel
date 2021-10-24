import { Egg } from '@/api/admin/egg';
import AdminBox from '@/components/admin/AdminBox';
import NodeSelect from '@/components/admin/servers/NodeSelect';
import { ServerImageContainer, ServerServiceContainer, ServerVariableContainer } from '@/components/admin/servers/ServerStartupContainer';
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
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import React, { useState } from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { object } from 'yup';
import { CreateServerRequest } from '@/api/admin/servers/createServer';

function InternalForm () {
    const { isSubmitting, isValid, values: { environment } } = useFormikContext<CreateServerRequest>();

    const [ egg, setEgg ] = useState<Egg | null>(null);

    return (
        <Form>
            <div css={tw`grid grid-cols-2 gap-y-6 gap-x-8 mb-16`}>
                <div css={tw`grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1`}>
                    <BaseSettingsBox>
                        <NodeSelect/>
                        <div css={tw`xl:col-span-2 bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                            <FormikSwitch
                                name={'startOnCompletion'}
                                label={'Start after installation'}
                                description={'Should the server be automatically started after it has been installed?'}
                            />
                        </div>
                    </BaseSettingsBox>
                    <FeatureLimitsBox/>
                    <ServerServiceContainer
                        egg={egg}
                        setEgg={setEgg}
                        /* TODO: Get lowest nest_id rather than always defaulting to 1 */
                        nestId={1}
                    />
                </div>
                <div css={tw`grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1`}>
                    <AdminBox icon={faNetworkWired} title={'Networking'} isLoading={isSubmitting}>
                        <div css={tw`grid grid-cols-1 gap-4 lg:gap-6`}>
                            <div>
                                <Label htmlFor={'allocationId'}>Primary Allocation</Label>
                                <Select id={'allocationId'} name={'allocationId'} disabled>
                                    <option value="">Select a node...</option>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor={'additionalAllocations'}>Additional Allocations</Label>
                                <Select id={'additionalAllocations'} name={'additionalAllocations'} disabled>
                                    <option value="">Select a node...</option>
                                </Select>
                            </div>
                        </div>
                    </AdminBox>
                    <ServerResourceBox/>
                    <ServerImageContainer/>
                </div>

                <AdminBox title={'Startup Command'} css={tw`relative w-full col-span-2`}>
                    <SpinnerOverlay visible={isSubmitting}/>

                    <Field
                        id={'startup'}
                        name={'startup'}
                        label={'Startup Command'}
                        type={'text'}
                        description={'Edit your server\'s startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}.'}
                        placeholder={egg?.startup || ''}
                    />
                </AdminBox>

                <div css={tw`col-span-2 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8`}>
                    {/* This ensures that no variables are rendered unless the environment has a value for the variable. */}
                    {egg?.relationships.variables?.filter(v => Object.keys(environment).find(e => e === v.environmentVariable) !== undefined).map((v, i) => (
                        <ServerVariableContainer
                            key={i}
                            variable={v}
                        />
                    ))}
                </div>

                <div css={tw`bg-neutral-700 rounded shadow-md px-4 py-3 col-span-2`}>
                    <div css={tw`flex flex-row`}>
                        <Button
                            type="submit"
                            size="small"
                            css={tw`ml-auto`}
                            disabled={isSubmitting || !isValid}
                        >
                            Create Server
                        </Button>
                    </div>
                </div>
            </div>
        </Form>
    );
}

export default () => {
    const submit = (r: CreateServerRequest, { setSubmitting }: FormikHelpers<CreateServerRequest>) => {
        console.log(r);
        setSubmitting(false);
    };

    return (
        <AdminContentBlock title={'New Server'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>New Server</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Add a new server to the panel.</p>
                </div>
            </div>

            <FlashMessageRender byKey={'server:create'} css={tw`mb-4`}/>

            <Formik
                onSubmit={submit}
                initialValues={{
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
                        // This value is inverted to have the switch be on when the
                        // OOM Killer is enabled, rather than when disabled.
                        oomDisabled: false,
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
                } as CreateServerRequest}
                validationSchema={object().shape({})}
            >
                <InternalForm/>
            </Formik>
        </AdminContentBlock>
    );
};
