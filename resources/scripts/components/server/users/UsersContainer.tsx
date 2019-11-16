import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons/faUserPlus';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { Subuser } from '@/state/server/subusers';
import { CSSTransition } from 'react-transition-group';
import classNames from 'classnames';
import PermissionEditor from '@/components/server/users/PermissionEditor';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons/faArrowLeft';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const [ editSubuser, setEditSubuser ] = useState<Subuser | null>(null);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const getSubusers = ServerContext.useStoreActions(actions => actions.subusers.getSubusers);

    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);
    const getPermissions = useStoreActions((actions: Actions<ApplicationStore>) => actions.permissions.getPermissions);

    useEffect(() => {
        if (!permissions.length) {
            getPermissions().catch(error => console.error(error));
        }
    }, [ permissions, getPermissions ]);

    useEffect(() => {
        getSubusers(uuid)
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
            });
    }, [ uuid, getSubusers ]);

    useEffect(() => {
        if (subusers.length > 0) {
            setLoading(false);
        }
    }, [ subusers ]);

    return (
        <div className={'flex my-10'}>
            <div className={'w-1/2'}>
                <h2 className={'text-neutral-300 mb-4'}>Subusers</h2>
                <div
                    className={classNames('border-t-4 grey-box mt-0', {
                        'border-cyan-400': editSubuser === null,
                        'border-neutral-400': editSubuser !== null,
                    })}
                >
                    {(loading || !permissions.length) ?
                        <div className={'w-full'}>
                            <Spinner centered={true}/>
                        </div>
                        :
                        !subusers.length ?
                            <p className={'text-sm'}>It looks like you don't have any subusers.</p>
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
                                            onClick={() => setEditSubuser(subuser)}
                                        >
                                            Edit
                                        </button>
                                        <button
                                            className={'ml-2 btn btn-xs btn-red btn-secondary'}
                                            onClick={() => setEditSubuser(null)}
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            ))
                    }
                </div>
                <div className={'flex justify-end mt-6'}>
                    <button className={'btn btn-primary btn-sm'}>
                        <FontAwesomeIcon icon={faUserPlus} className={'mr-1'}/> New User
                    </button>
                </div>
            </div>
            {editSubuser &&
            <CSSTransition timeout={250} classNames={'fade'} appear={true} in={true}>
                <div className={'flex-1 ml-6'}>
                    <h2 className={'flex items-center text-neutral-300 mb-4'}>
                        <span onClick={() => setEditSubuser(null)}>
                            <FontAwesomeIcon
                                icon={faArrowLeft}
                                className={'text-base mr-2 text-neutral-200 hover:text-neutral-100 cursor-pointer'}
                            />
                        </span>
                        Edit {editSubuser.email}
                    </h2>
                    <div className={'border-t-4 border-cyan-400 grey-box mt-0 p-4'}>
                        <React.Suspense fallback={'Loading...'}>
                            <PermissionEditor
                                defaultPermissions={editSubuser.permissions}
                            />
                        </React.Suspense>
                    </div>
                </div>
            </CSSTransition>
            }
        </div>
    );
};
