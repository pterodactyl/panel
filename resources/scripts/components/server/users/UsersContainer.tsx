import React, { useEffect, useState } from 'react';
import ReactGA from 'react-ga';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Spinner from '@/components/elements/Spinner';
import AddSubuserButton from '@/components/server/users/AddSubuserButton';
import UserRow from '@/components/server/users/UserRow';
import FlashMessageRender from '@/components/FlashMessageRender';
import getServerSubusers from '@/api/server/users/getServerSubusers';
import { httpErrorToHuman } from '@/api/http';
import Can from '@/components/elements/Can';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default () => {
    const [ loading, setLoading ] = useState(true);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const setSubusers = ServerContext.useStoreActions(actions => actions.subusers.setSubusers);

    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);
    const getPermissions = useStoreActions((actions: Actions<ApplicationStore>) => actions.permissions.getPermissions);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('users');
        getServerSubusers(uuid)
            .then(subusers => {
                setSubusers(subusers);
                setLoading(false);
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'users', message: httpErrorToHuman(error) });
            });
    }, []);

    useEffect(() => {
        ReactGA.pageview(location.pathname)
    }, []);

    useEffect(() => {
        getPermissions().catch(error => {
            addError({ key: 'users', message: httpErrorToHuman(error) });
            console.error(error);
        });
    }, []);

    if (!subusers.length && (loading || !Object.keys(permissions).length)) {
        return <Spinner size={'large'} centered={true}/>;
    }

    return (
        <PageContentBlock>
            <FlashMessageRender byKey={'users'} className={'mb-4'}/>
            {!subusers.length ?
                <p className={'text-center text-sm text-neutral-400'}>
                    It looks like you don't have any subusers.
                </p>
                :
                subusers.map(subuser => (
                    <UserRow key={subuser.uuid} subuser={subuser}/>
                ))
            }
            <Can action={'user.create'}>
                <div className={'flex justify-end mt-6'}>
                    <AddSubuserButton/>
                </div>
            </Can>
        </PageContentBlock>
    );
};
