import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Mount } from '@/api/admin/mounts/getMounts';
import getMount from '@/api/admin/mounts/getMount';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

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
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const mount = Context.useStoreState(state => state.mount);
    const setMount = Context.useStoreActions(actions => actions.setMount);

    useEffect(() => {
        clearFlashes('mount');

        getMount(Number(match.params?.id))
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
                <FlashMessageRender byKey={'mount'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Mount - ' + mount.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{mount.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{mount.description}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'mount'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <MountEditContainer/>
        </Context.Provider>
    );
};
