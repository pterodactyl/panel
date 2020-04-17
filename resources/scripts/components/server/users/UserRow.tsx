import React, { useState } from 'react';
import { Subuser } from '@/state/server/subusers';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPencilAlt } from '@fortawesome/free-solid-svg-icons/faPencilAlt';
import RemoveSubuserButton from '@/components/server/users/RemoveSubuserButton';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';
import { faUnlockAlt } from '@fortawesome/free-solid-svg-icons/faUnlockAlt';
import { faUserLock } from '@fortawesome/free-solid-svg-icons/faUserLock';
import classNames from 'classnames';
import Can from '@/components/elements/Can';

interface Props {
    subuser: Subuser;
}

export default ({ subuser }: Props) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <div className={'grey-row-box mb-2'}>
            {visible &&
            <EditSubuserModal
                appear={true}
                visible={true}
                subuser={subuser}
                onDismissed={() => setVisible(false)}
            />
            }
            <div className={'w-10 h-10 rounded-full bg-white border-2 border-inset border-neutral-800 overflow-hidden'}>
                <img className={'f-full h-full'} src={`${subuser.image}?s=400`}/>
            </div>
            <div className={'ml-4 flex-1'}>
                <p className={'text-sm'}>{subuser.email}</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>
                    &nbsp;
                    <FontAwesomeIcon
                        icon={subuser.twoFactorEnabled ? faUserLock : faUnlockAlt}
                        className={classNames('fa-fw', {
                            'text-red-400': !subuser.twoFactorEnabled,
                        })}
                    />
                    &nbsp;
                </p>
                <p className={'text-2xs text-neutral-500 uppercase'}>2FA Enabled</p>
            </div>
            <div className={'ml-4'}>
                <p className={'font-medium text-center'}>
                    {subuser.permissions.filter(permission => permission !== 'websocket.connect').length}
                </p>
                <p className={'text-2xs text-neutral-500 uppercase'}>Permissions</p>
            </div>
            <button
                type={'button'}
                aria-label={'Edit subuser'}
                className={'block text-sm p-2 text-neutral-500 hover:text-neutral-100 transition-colors duration-150 mx-4'}
                onClick={() => setVisible(true)}
            >
                <FontAwesomeIcon icon={faPencilAlt}/>
            </button>
            <Can action={'user.delete'}>
                <RemoveSubuserButton subuser={subuser}/>
            </Can>
        </div>
    );
};
