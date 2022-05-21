import tw from 'twin.macro';
import * as Icon from 'react-feather';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';

export default () => {
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <EditSubuserModal visible={visible} onModalDismissed={() => setVisible(false)}/>
            <Button onClick={() => setVisible(true)}>
                <Icon.UserPlus css={tw`mr-1`} /> New User
            </Button>
        </>
    );
};
