import { useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import tw from 'twin.macro';
import { object } from 'yup';

import { useServerFromRoute } from '@/api/admin/server';
import type { Values } from '@/api/admin/servers/updateServer';
import updateServer from '@/api/admin/servers/updateServer';
import ServerDeleteButton from '@/components/admin/servers/ServerDeleteButton';
import BaseSettingsBox from '@/components/admin/servers/settings/BaseSettingsBox';
import FeatureLimitsBox from '@/components/admin/servers/settings/FeatureLimitsBox';
import NetworkingBox from '@/components/admin/servers/settings/NetworkingBox';
import ServerResourceBox from '@/components/admin/servers/settings/ServerResourceBox';
import { Button } from '@/components/elements/button';

export default () => {
    const { data: server } = useServerFromRoute();
    const { clearFlashes, clearAndAddHttpError } = useStoreActions(actions => actions.flashes);

    if (!server) return null;

    const submit = (values: Values, { setSubmitting, setFieldValue }: FormikHelpers<Values>) => {
        clearFlashes('server');

        updateServer(server.id, values)
            .then(() => {
                // setServer({ ...server, ...s });

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
                    oomKiller: server.limits.oomKiller,
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
            validationSchema={object().shape({})}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8 mb-16`}>
                        <div css={tw`grid grid-cols-1 gap-y-6`}>
                            <BaseSettingsBox />
                            <FeatureLimitsBox />
                            <NetworkingBox />
                        </div>

                        <div css={tw`flex flex-col`}>
                            <ServerResourceBox />

                            <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mt-6`}>
                                <div css={tw`flex flex-row`}>
                                    <ServerDeleteButton />

                                    <Button type="submit" className="ml-auto" disabled={isSubmitting || !isValid}>
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
};
