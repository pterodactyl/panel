import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import getEgg, { Egg } from '@/api/admin/eggs/getEgg';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

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

const EggEditContainer = () => {
    const match = useRouteMatch<{ nestId?: string, id?: string }>();

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
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{egg.name}</h2>
                    {
                        (egg.description || '').length < 1 ?
                            <p css={tw`text-base text-neutral-400`}>
                                <span css={tw`italic`}>No description</span>
                            </p>
                            :
                            <p css={tw`text-base text-neutral-400`}>{egg.description}</p>
                    }
                </div>
            </div>

            <FlashMessageRender byKey={'egg'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <EggEditContainer/>
        </Context.Provider>
    );
};
