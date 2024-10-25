import type { Action, Actions } from 'easy-peasy';
import { action, createContextStore, useStoreActions } from 'easy-peasy';
import { useEffect, useState } from 'react';
import { Route, Routes, useParams } from 'react-router-dom';
import tw from 'twin.macro';

import { getUser } from '@/api/admin/users';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import UserAboutContainer from '@/components/admin/users/UserAboutContainer';
import UserServers from '@/components/admin/users/UserServers';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import type { ApplicationStore } from '@/state';
import type { User } from '@definitions/admin';

interface ctx {
    user: User | undefined;
    setUser: Action<ctx, User | undefined>;
}

export const Context = createContextStore<ctx>({
    user: undefined,

    setUser: action((state, payload) => {
        state.user = payload;
    }),
});

const UserRouter = () => {
    const params = useParams<'id'>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [loading, setLoading] = useState(true);

    const user = Context.useStoreState(state => state.user);
    const setUser = Context.useStoreActions(actions => actions.setUser);

    useEffect(() => {
        clearFlashes('user');

        getUser(Number(params.id), ['role'])
            .then(user => setUser(user))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'user', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || user === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'user'} css={tw`mb-4`} />

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'} />
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'User - ' + user.id}>
            <div css={tw`w-full flex flex-row items-center mb-4`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{user.email}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        {user.uuid}
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'user'} css={tw`mb-4`} />

            <SubNavigation>
                <SubNavigationLink to={`/admin/users/${params.id}`} name={'About'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                        />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`/admin/users/${params.id}/servers`} name={'Servers'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        />
                    </svg>
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path="" element={<UserAboutContainer />} />
                <Route path="servers" element={<UserServers />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <UserRouter />
        </Context.Provider>
    );
};
