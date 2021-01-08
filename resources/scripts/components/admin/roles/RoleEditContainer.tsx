import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Role } from '@/api/admin/roles/getRoles';
import getRole from '@/api/admin/roles/getRole';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

interface ctx {
    role: Role | undefined;
    setRole: Action<ctx, Role | undefined>;
}

export const Context = createContextStore<ctx>({
    role: undefined,

    setRole: action((state, payload) => {
        state.role = payload;
    }),
});

const RoleEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const role = Context.useStoreState(state => state.role);
    const setRole = Context.useStoreActions(actions => actions.setRole);

    useEffect(() => {
        clearFlashes('role');

        getRole(Number(match.params?.id))
            .then(role => setRole(role))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'role', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || role === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'role'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Role - ' + role.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{role.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{role.description}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'role'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <RoleEditContainer/>
        </Context.Provider>
    );
};
