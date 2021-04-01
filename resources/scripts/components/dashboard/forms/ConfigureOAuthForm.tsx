import React, { useState } from 'react';
import SetupOAuthModal from '@/components/dashboard/forms/SetupOAuthModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

export default () => {
    const [ visible, setVisible ] = useState(false);

    return (
        <div>
            {visible &&
            <SetupOAuthModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p className={'text-sm'}>
                Click the button below to setup your linked OAuth accounts
            </p>
            <div css={tw`mt-6`}>
                <Button
                    color={'green'}
                    isSecondary
                    onClick={() => setVisible(true)}
                >
                    Begin Setup
                </Button>
            </div>
        </div>
    );
};
