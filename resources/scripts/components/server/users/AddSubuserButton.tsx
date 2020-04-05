import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons/faUserPlus';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';

export default () => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            {visible && <EditSubuserModal
                appear={true}
                visible={true}
                onDismissed={() => setVisible(false)}
            />}
            <button className={'btn btn-primary btn-sm'} onClick={() => setVisible(true)}>
                <FontAwesomeIcon icon={faUserPlus} className={'mr-1'}/> New User
            </button>
        </>
    );
};
