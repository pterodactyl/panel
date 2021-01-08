import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Location } from '@/api/admin/locations/getLocations';
import getLocation from '@/api/admin/locations/getLocation';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

interface ctx {
    location: Location | undefined;
    setLocation: Action<ctx, Location | undefined>;
}

export const Context = createContextStore<ctx>({
    location: undefined,

    setLocation: action((state, payload) => {
        state.location = payload;
    }),
});

const LocationEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const location = Context.useStoreState(state => state.location);
    const setLocation = Context.useStoreActions(actions => actions.setLocation);

    useEffect(() => {
        clearFlashes('location');

        getLocation(Number(match.params?.id))
            .then(location => setLocation(location))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'location', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || location === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'location'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Location - ' + location.short}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{location.short}</h2>
                    <p css={tw`text-base text-neutral-400`}>{location.long}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'location'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <LocationEditContainer/>
        </Context.Provider>
    );
};
