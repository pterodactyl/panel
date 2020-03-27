import React, { useState } from 'react';
import { Subuser } from '@/state/server/subusers';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPencilAlt } from '@fortawesome/free-solid-svg-icons/faPencilAlt';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import RemoveSubuserButton from '@/components/server/users/RemoveSubuserButton';

interface Props {
    subuser: Subuser;
}

export default ({ subuser }: Props) => {
    const appendSubuser = ServerContext.useStoreActions(actions => actions.subusers.appendSubuser);

    return (
        <div className={'grey-row-box mb-2'}>
            <div className={'w-10 h-10 rounded-full bg-white border-2 border-inset border-neutral-800 overflow-hidden'}>
                <img className={'f-full h-full'} src={`${subuser.image}?s=400`}/>
            </div>
            <div className={'ml-4 flex-1'}>
                <p className={'text-sm'}>{subuser.email}</p>
            </div>
            <button
                type={'button'}
                aria-label={'Edit subuser'}
                className={'block text-sm p-2 text-neutral-500 hover:text-neutral-100 transition-colors duration-150 mr-4'}
                onClick={() => null}
            >
                <FontAwesomeIcon icon={faPencilAlt}/>
            </button>
            <RemoveSubuserButton subuser={subuser}/>
        </div>
    );
};
