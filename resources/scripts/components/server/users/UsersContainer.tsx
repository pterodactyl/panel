import React, { useEffect, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons/faUserPlus';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { Subuser } from '@/state/server/subusers';
import { CSSTransition } from 'react-transition-group';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const [ editSubuser, setEditSubuser ] = useState<Subuser | null>(null);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const getSubusers = ServerContext.useStoreActions(actions => actions.subusers.getSubusers);

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
                <div className={'border-t-4 border-primary-400 grey-box mt-0'}>
                    {loading ?
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
                    <h2 className={'text-neutral-300 mb-4'}>Edit {editSubuser.email}</h2>
                    <div className={'border-t-4 border-primary-400 grey-box mt-0'}>
                        <p>Edit permissions here.</p>
                    </div>
                </div>
            </CSSTransition>
            }
        </div>
    );
};
