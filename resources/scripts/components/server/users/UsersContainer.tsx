import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Spinner from '@/components/elements/Spinner';
import AddSubuserButton from '@/components/server/users/AddSubuserButton';

export default () => {
    const [ loading, setLoading ] = useState(true);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const getSubusers = ServerContext.useStoreActions(actions => actions.subusers.getSubusers);

    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);
    const getPermissions = useStoreActions((actions: Actions<ApplicationStore>) => actions.permissions.getPermissions);

    useEffect(() => {
        getPermissions().catch(error => console.error(error));
    }, []);

    useEffect(() => {
        getSubusers(uuid)
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
            });
    }, [ uuid, getSubusers ]);

    useEffect(() => {
        setLoading(!subusers);
    }, [ subusers ]);

    if (loading || !Object.keys(permissions).length) {
        return <Spinner size={'large'} centered={true}/>;
    }

    return (
        <div className={'my-10'}>
            {!subusers.length ?
                <p className={'text-center text-sm text-neutral-400'}>
                    It looks like you don't have any subusers.
                </p>
                :
                subusers.map(subuser => (
                    <div key={subuser.uuid} className={'flex items-center w-full'}>
                        <img
                            className={'w-10 h-10 rounded-full bg-white border-2 border-inset border-neutral-800'}
                            src={`${subuser.image}?s=400`}
                        />
                        <div className={'ml-4 flex-1'}>
                            <p className={'text-sm'}>{subuser.email}</p>
                        </div>
                        <div className={'ml-4'}>
                            <button
                                className={'btn btn-xs btn-primary'}
                            >
                                Edit
                            </button>
                            <button
                                className={'ml-2 btn btn-xs btn-red btn-secondary'}
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                ))
            }
            <div className={'flex justify-end mt-6'}>
                <AddSubuserButton/>
            </div>
        </div>
    );
};
