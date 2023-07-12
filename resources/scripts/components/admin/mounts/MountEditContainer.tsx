import type { Action, Actions } from 'easy-peasy';
import { action, createContextStore, useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import tw from 'twin.macro';

import type { Mount } from '@/api/admin/mounts/getMounts';
import getMount from '@/api/admin/mounts/getMount';
import updateMount from '@/api/admin/mounts/updateMount';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import MountDeleteButton from '@/components/admin/mounts/MountDeleteButton';
import MountForm from '@/components/admin/mounts/MountForm';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import type { ApplicationStore } from '@/state';

interface ctx {
    mount: Mount | undefined;
    setMount: Action<ctx, Mount | undefined>;
}

export const Context = createContextStore<ctx>({
    mount: undefined,

    setMount: action((state, payload) => {
        state.mount = payload;
    }),
});

const MountEditContainer = () => {
    const navigate = useNavigate();
    const params = useParams<'id'>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [loading, setLoading] = useState(true);

    const mount = Context.useStoreState(state => state.mount);
    const setMount = Context.useStoreActions(actions => actions.setMount);

    const submit = (
        { name, description, source, target, readOnly, userMountable }: any,
        { setSubmitting }: FormikHelpers<any>,
    ) => {
        if (mount === undefined) {
            return;
        }

        clearFlashes('mount');

        updateMount(mount.id, name, description, source, target, readOnly === '1', userMountable === '1')
            .then(() =>
                setMount({
                    ...mount,
                    name,
                    description,
                    source,
                    target,
                    readOnly: readOnly === '1',
                    userMountable: userMountable === '1',
                }),
            )
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'mount', error });
            })
            .then(() => setSubmitting(false));
    };

    useEffect(() => {
        clearFlashes('mount');

        getMount(Number(params.id))
            .then(mount => setMount(mount))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'mount', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || mount === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'mount'} css={tw`mb-4`} />

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'} />
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Mount - ' + mount.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{mount.name}</h2>
                    {(mount.description || '').length < 1 ? (
                        <p css={tw`text-base text-neutral-400`}>
                            <span css={tw`italic`}>No description</span>
                        </p>
                    ) : (
                        <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                            {mount.description}
                        </p>
                    )}
                </div>
            </div>

            <FlashMessageRender byKey={'mount'} css={tw`mb-4`} />

            <MountForm
                action={'Save Changes'}
                title={'Edit Mount'}
                initialValues={{
                    name: mount.name,
                    description: mount.description || '',
                    source: mount.source,
                    target: mount.target,
                    readOnly: mount.readOnly ? '1' : '0',
                    userMountable: mount.userMountable ? '1' : '0',
                }}
                onSubmit={submit}
            >
                <div css={tw`flex`}>
                    <MountDeleteButton mountId={mount.id} onDeleted={() => navigate('/admin/mounts')} />
                </div>
            </MountForm>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <MountEditContainer />
        </Context.Provider>
    );
};
