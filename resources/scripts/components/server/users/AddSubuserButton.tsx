import tw from 'twin.macro';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';

export default () => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <EditSubuserModal visible={visible} onModalDismissed={() => setVisible(false)}/>
            <Button onClick={() => setVisible(true)}>
                <FontAwesomeIcon icon={faUserPlus} css={tw`mr-1`}/> New User
            </Button>
        </>
    );
};
