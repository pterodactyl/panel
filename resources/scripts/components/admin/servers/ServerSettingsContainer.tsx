import getAllocations from '@/api/admin/nodes/getAllocations';
import { Server } from '@/api/admin/servers/getServers';
import ServerDeleteButton from '@/components/admin/servers/ServerDeleteButton';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import SelectField, { AsyncSelectField, Option } from '@/components/elements/SelectField';
import { faBalanceScale, faCogs, faConciergeBell, faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';
import updateServer, { Values } from '@/api/admin/servers/updateServer';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Context, ServerIncludes } from '@/components/admin/servers/ServerRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import Button from '@/components/elements/Button';
import FormikSwitch from '@/components/elements/FormikSwitch';
import BaseSettingsBox from '@/components/admin/servers/settings/BaseSettingsBox';

export function ServerFeatureContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faConciergeBell} title={'Feature Limits'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6`}>
                <Field
                    id={'featureLimits.allocations'}
                    name={'featureLimits.allocations'}
                    label={'Allocation Limit'}
                    type={'number'}
                    description={'The total number of allocations a user is allowed to create for this server.'}
                />

                <Field
                    id={'featureLimits.backups'}
                    name={'featureLimits.backups'}
                    label={'Backup Limit'}
                    type={'number'}
                    description={'The total number of backups that can be created for this server.'}
                />

                <Field
                    id={'featureLimits.databases'}
                    name={'featureLimits.databases'}
                    label={'Database Limit'}
                    type={'number'}
                    description={'The total number of databases a user is allowed to create for this server.'}
                />
            </div>
        </AdminBox>
    );
}

export function ServerAllocationsContainer ({ server }: { server: Server }) {
    const { isSubmitting } = useFormikContext();

    const loadOptions = async (inputValue: string, callback: (options: Option[]) => void) => {
        const allocations = await getAllocations(server.nodeId, { ip: inputValue, server_id: '0' });
        callback(allocations.map(a => {
            return { value: a.id.toString(), label: a.getDisplayText() };
        }));
    };

    return (
        <AdminBox icon={faNetworkWired} title={'Networking'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`mb-6`}>
                <Label>Primary Allocation</Label>
                <Select
                    id={'allocationId'}
                    name={'allocationId'}
                >
                    {server.relations?.allocations?.map(a => (
                        <option key={a.id} value={a.id}>{a.getDisplayText()}</option>
                    ))}
                </Select>
            </div>

            <AsyncSelectField
                id={'addAllocations'}
                name={'addAllocations'}
                label={'Add Allocations'}
                loadOptions={loadOptions}
                isMulti
                css={tw`mb-6`}
            />

            <SelectField
                id={'removeAllocations'}
                name={'removeAllocations'}
                label={'Remove Allocations'}
                options={server.relations?.allocations?.map(a => {
                    return { value: a.id.toString(), label: a.getDisplayText() };
                }) || []}
                isMulti
                isSearchable
                css={tw`mb-2`}
            />
        </AdminBox>
    );
}

export function ServerResourceContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faBalanceScale} title={'Resources'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                    <Field
                        id={'limits.cpu'}
                        name={'limits.cpu'}
                        label={'CPU Limit'}
                        type={'text'}
                        description={'Each thread on the system is considered to be 100%. Setting this value to 0 will allow the server to use CPU time without restriction.'}
                    />
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                    <Field
                        id={'limits.threads'}
                        name={'limits.threads'}
                        label={'CPU Pinning'}
                        type={'text'}
                        description={'Advanced: Enter the specific CPU cores that this server can run on, or leave blank to allow all cores. This can be a single number, and or a comma seperated list, and or a dashed range. Example: 0, 0-1,3, or 0,1,3,4.  It is recommended to leave this value blank and let the CPU handle balancing the load.'}
                    />
                </div>
            </div>

            <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                    <Field
                        id={'limits.memory'}
                        name={'limits.memory'}
                        label={'Memory Limit'}
                        type={'number'}
                        description={'The maximum amount of memory allowed for this container. Setting this to 0 will allow unlimited memory in a container.'}
                    />
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                    <Field
                        id={'limits.swap'}
                        name={'limits.swap'}
                        label={'Swap Limit'}
                        type={'number'}
                    />
                </div>
            </div>

            <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                    <Field
                        id={'limits.disk'}
                        name={'limits.disk'}
                        label={'Disk Limit'}
                        type={'number'}
                        description={'This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available. Set to 0 to allow unlimited disk usage.'}
                    />
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                    <Field
                        id={'limits.io'}
                        name={'limits.io'}
                        label={'Block IO Proportion'}
                        type={'number'}
                        description={'Advanced: The IO performance of this server relative to other running containers on the system. Value should be between 10 and 1000.'}
                    />
                </div>
            </div>

            <div css={tw`mb-2 md:w-full md:flex md:flex-row`}>
                <div css={tw`bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                    <FormikSwitch
                        name={'limits.oomDisabled'}
                        label={'Out of Memory Killer'}
                        description={'Enabling the Out of Memory Killer may cause server processes to exit unexpectedly.'}
                    />
                </div>
            </div>
        </AdminBox>
    );
}

export default function ServerSettingsContainer2 ({ server }: { server: Server }) {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const setServer = Context.useStoreActions(actions => actions.setServer);

    const submit = (values: Values, { setSubmitting, setFieldValue }: FormikHelpers<Values>) => {
        clearFlashes('server');

        // This value is inverted to have the switch be on when the
        // OOM Killer is enabled, rather than when disabled.
        values.limits.oomDisabled = !values.limits.oomDisabled;

        updateServer(server.id, values, ServerIncludes)
            .then(s => {
                setServer({ ...server, ...s });

                // TODO: Figure out how to properly clear react-selects for allocations.
                setFieldValue('addAllocations', []);
                setFieldValue('removeAllocations', []);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'server', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                externalId: server.externalId || '',
                name: server.name,
                ownerId: server.ownerId,

                limits: {
                    memory: server.limits.memory,
                    swap: server.limits.swap,
                    disk: server.limits.disk,
                    io: server.limits.io,
                    cpu: server.limits.cpu,
                    threads: server.limits.threads || '',
                    // This value is inverted to have the switch be on when the
                    // OOM Killer is enabled, rather than when disabled.
                    oomDisabled: !server.limits.oomDisabled,
                },

                featureLimits: {
                    allocations: server.featureLimits.allocations,
                    backups: server.featureLimits.backups,
                    databases: server.featureLimits.databases,
                },

                allocationId: server.allocationId,
                addAllocations: [] as number[],
                removeAllocations: [] as number[],
            }}
            validationSchema={object().shape({
            })}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 mb-16`}>
                        <div css={tw`flex flex-col`}>
                            <div css={tw`flex mb-6`}>
                                <BaseSettingsBox server={server}/>
                            </div>

                            <div css={tw`flex mb-6`}>
                                <ServerFeatureContainer/>
                            </div>

                            <div css={tw`flex`}>
                                <ServerAllocationsContainer server={server}/>
                            </div>
                        </div>

                        <div css={tw`flex flex-col`}>
                            <div css={tw`flex mb-6`}>
                                <ServerResourceContainer/>
                            </div>

                            <div css={tw`bg-neutral-700 rounded shadow-md py-2 px-6`}>
                                <div css={tw`flex flex-row`}>
                                    <ServerDeleteButton
                                        serverId={server?.id}
                                        onDeleted={() => history.push('/admin/servers')}
                                    />
                                    <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                        Save Changes
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
