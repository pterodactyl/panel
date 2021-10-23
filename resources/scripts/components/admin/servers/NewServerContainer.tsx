import { Egg } from '@/api/admin/egg';
import AdminBox from '@/components/admin/AdminBox';
import { ServerImageContainer, ServerServiceContainer, ServerVariableContainer } from '@/components/admin/servers/ServerStartupContainer';
import BaseSettingsBox from '@/components/admin/servers/settings/BaseSettingsBox';
import FeatureLimitsBox from '@/components/admin/servers/settings/FeatureLimitsBox';
import ServerResourceBox from '@/components/admin/servers/settings/ServerResourceBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import { Form, Formik } from 'formik';
import React, { useState } from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { object } from 'yup';
import { Values } from '@/api/admin/servers/updateServer';

export default () => {
    const [ egg, setEgg ] = useState<Egg | null>(null);

    const submit = (_: Values) => {
        //
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
                    ownerId: 0,
                    limits: {
                        memory: 0,
                        swap: 0,
                        disk: 0,
                        io: 0,
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
                    allocationId: 0,
                    addAllocations: [] as number[],
                    removeAllocations: [] as number[],
                }}
                validationSchema={object().shape({})}
            >
                {({ isSubmitting, isValid }) => (
                    <Form>
                        <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 mb-16`}>
                            <div css={tw`grid grid-cols-1 gap-y-6`}>
                                <BaseSettingsBox/>
                                <FeatureLimitsBox/>
                                {/* TODO: in networking box only show primary allocation and additional allocations */}
                                {/* TODO: add node select */}
                                <ServerServiceContainer
                                    egg={egg}
                                    setEgg={setEgg}
                                    /* TODO: Get lowest nest_id rather than always defaulting to 1 */
                                    nestId={1}
                                />
                            </div>
                            <div css={tw`grid grid-cols-1 gap-y-6`}>
                                <AdminBox icon={faNetworkWired} title={'Networking'} isLoading={isSubmitting}>
                                    <div css={tw`grid grid-cols-1 gap-4 lg:gap-6`}>
                                        <div>
                                            <Label htmlFor={'allocationId'}>Primary Allocation</Label>
                                            <Select id={'allocationId'} name={'allocationId'}/>
                                        </div>
                                        <div>
                                            <Label htmlFor={'additionalAllocations'}>Additional Allocations</Label>
                                            <Select id={'additionalAllocations'} name={'additionalAllocations'}/>
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
                                {egg?.relationships.variables?.map((v, i) => (
                                    <ServerVariableContainer
                                        key={i}
                                        variable={v}
                                        defaultValue={v.defaultValue}
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
                )}
            </Formik>
        </AdminContentBlock>
    );
};
