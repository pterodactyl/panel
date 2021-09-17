import EggInstallContainer from '@/components/admin/nests/eggs/EggInstallContainer';
import EggVariablesContainer from '@/components/admin/nests/eggs/EggVariablesContainer';
import React, { useEffect, useState } from 'react';
import { useLocation } from 'react-router';
import tw from 'twin.macro';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import getEgg, { Egg } from '@/api/admin/eggs/getEgg';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import EggSettingsContainer from '@/components/admin/nests/eggs/EggSettingsContainer';

interface ctx {
    egg: Egg | undefined;
    setEgg: Action<ctx, Egg | undefined>;
}

export const Context = createContextStore<ctx>({
    egg: undefined,

    setEgg: action((state, payload) => {
        state.egg = payload;
    }),
});

const EggRouter = () => {
    const location = useLocation();
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const egg = Context.useStoreState(state => state.egg);
    const setEgg = Context.useStoreActions(actions => actions.setEgg);

    useEffect(() => {
        clearFlashes('egg');

        getEgg(Number(match.params?.id))
            .then(egg => setEgg(egg))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'egg', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || egg === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'egg'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Egg - ' + egg.name}>
            <div css={tw`w-full flex flex-row items-center mb-4`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{egg.name}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{egg.uuid}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'egg'} css={tw`mb-4`}/>

            <SubNavigation>
                <SubNavigationLink to={`${match.url}`} name={'About'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path clipRule="evenodd" fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/variables`} name={'Variables'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/install`} name={'Install Script'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path clipRule="evenodd" fillRule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" />
                    </svg>
                </SubNavigationLink>
            </SubNavigation>

            <Switch location={location}>
                <Route path={`${match.path}`} exact>
                    <EggSettingsContainer/>
                </Route>

                <Route path={`${match.path}/variables`} exact>
                    <EggVariablesContainer/>
                </Route>

                <Route path={`${match.path}/install`} exact>
                    <EggInstallContainer egg={egg}/>
                </Route>
            </Switch>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <EggRouter/>
        </Context.Provider>
    );
};
