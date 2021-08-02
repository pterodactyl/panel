import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object } from 'yup';
import updateServer from '@/api/admin/servers/updateServer';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Context } from '@/components/admin/servers/ServerRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import OwnerSelect from '@/components/admin/servers/OwnerSelect';
import Button from '@/components/elements/Button';
import FormikSwitch from '@/components/elements/FormikSwitch';

interface Values {
    id: number;
    externalId: string;
    uuid: string;
    identifier: string;
    name: string;
    description: string;

    memory: number;
    swap: number;
    disk: number;
    io: number;
    cpu: number;
    threads: string;

    databases: number;
    allocations: number;
    backups: number;

    ownerId: number;
    nodeId: number;
    allocationId: number;
    nestId: number;
    eggId: number;
}

const ServerFeatureContainer = () => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Feature Limits'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <Field
                            id={'databases'}
                            name={'databases'}
                            label={'Database Limit'}
                            type={'number'}
                            description={'The total number of databases a user is allowed to create for this server.'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'allocations'}
                            name={'allocations'}
                            label={'Allocation Limit'}
                            type={'number'}
                            description={'The total number of allocations a user is allowed to create for this server.'}
                        />
                    </div>
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mb-0`}>
                        <Field
                            id={'backups'}
                            name={'backup'}
                            label={'Backup Limit'}
                            type={'number'}
                            description={'The total number of backups that can be created for this server.'}
                        />
                    </div>
                </div>

            </Form>
        </AdminBox>
    );
};

const ServerResourceContainer = () => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Resource Management'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <Field
                            id={'cpu'}
                            name={'cpu'}
                            label={'CPU Limit'}
                            type={'string'}
                            description={'Each physical core on the system is considered to be 100%. Setting this value to 0 will allow a server to use CPU time without restrictions.'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'threads'}
                            name={'threads'}
                            label={'CPU Pinning'}
                            type={'string'}
                            description={'Advanced: Enter the specific CPU cores that this process can run on, or leave blank to allow all cores. This can be a single number, or a comma seperated list. Example: 0, 0-1,3, or 0,1,3,4.'}
                        />
                    </div>
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <Field
                            id={'memory'}
                            name={'memory'}
                            label={'Memory Limit'}
                            type={'number'}
                            description={'The maximum amount of memory allowed for this container. Setting this to 0 will allow unlimited memory in a container.'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'swap'}
                            name={'swap'}
                            label={'Swap Limit'}
                            type={'number'}
                        />
                    </div>
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <Field
                            id={'disk'}
                            name={'disk'}
                            label={'Disk Limit'}
                            type={'number'}
                            description={'This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available. Set to 0 to allow unlimited disk usage.'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'io'}
                            name={'io'}
                            label={'Block IO Proportion'}
                            type={'number'}
                            description={'Advanced: The IO performance of this server relative to other running containers on the system. Value should be between 10 and 1000.'}
                        />
                    </div>
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <FormikSwitch
                            name={'oom'}
                            label={'Out of Memory Killer'}
                            description={'Enabling OOM killer may cause server processes to exit unexpectedly. '}
                        />
                    </div>
                </div>

            </Form>
        </AdminBox>
    );
};

const ServerSettingsContainer = () => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Settings'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <Field
                            id={'name'}
                            name={'name'}
                            label={'Server Name'}
                            type={'string'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'externalId'}
                            name={'externalId'}
                            label={'External Identifier'}
                            type={'number'}
                        />
                    </div>
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                        <OwnerSelect selected={null}/>
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                        <Field
                            id={'description'}
                            name={'description'}
                            label={'Server Description'}
                            type={'string'}
                        />
                    </div>
                </div>
            </Form>
        </AdminBox>
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const server = Context.useStoreState(state => state.server);
    const setServer = Context.useStoreActions(actions => actions.setServer);

    if (server === undefined) {
        return (
            <></>
        );
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('server');

        updateServer(server.id, values)
            .then(() => setServer({ ...server, ...values }))
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
                id: server.id,
                externalId: server.externalId || '',
                uuid: server.uuid,
                identifier: server.identifier,
                name: server.name,
                description: server.description,

                memory: server.limits.memory,
                swap: server.limits.swap,
                disk: server.limits.disk,
                io: server.limits.io,
                cpu: server.limits.cpu,
                threads: server.limits.threads || '',

                databases: server.featureLimits.databases,
                allocations: server.featureLimits.allocations,
                backups: server.featureLimits.backups,

                ownerId: server.ownerId,
                nodeId: server.nodeId,
                allocationId: server.allocationId,
                nestId: server.nestId,
                eggId: server.eggId,
            }}
            validationSchema={object().shape({
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <div css={tw`flex flex-col lg:flex-row`}>
                        <div css={tw`flex flex-col w-full mt-4 ml-0 lg:w-1/2 lg:ml-2 lg:mt-0`}>
                            <div css={tw`flex flex-col w-full mr-0 lg:mr-2`}>
                                <ServerSettingsContainer/>
                            </div>
                            <div css={tw`flex flex-col w-full mt-4 mr-0 lg:mr-2`}>
                                <ServerFeatureContainer/>
                            </div>
                        </div>
                        <div css={tw`flex flex-col w-full mt-4 ml-0 lg:w-1/2 lg:ml-2 lg:mt-0`}>
                            <div css={tw`flex flex-col w-full mr-0 lg:mr-2`}>
                                <ServerResourceContainer/>
                            </div>
                            <div css={tw`py-2 pr-6 mt-4 rounded shadow-md bg-neutral-700`}>
                                <div css={tw`flex flex-row`}>
                                    <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                        Save Changes
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )
            }
        </Formik>
    );
};
