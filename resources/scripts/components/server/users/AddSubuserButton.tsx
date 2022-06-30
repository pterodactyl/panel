import React, { useState } from 'react';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
export default () => {
    const [visible, setVisible] = useState(false);

    return (
        <>
            <EditSubuserModal visible={visible} onModalDismissed={() => setVisible(false)} />
            <Button css={tw`w-full sm:w-auto`} onClick={() => setVisible(true)}>New User</Button>
        </>
    );
};
