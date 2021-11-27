import React, { useState } from 'react';
import SetupOAuthModal from '@/components/dashboard/forms/SetupOAuthModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { useStoreState } from 'easy-peasy';

export default () => {
    const [ visible, setVisible ] = useState(false);
    const { enabled: oauthEnabled } = useStoreState(state => state.settings.data!.oauth);

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
                {oauthEnabled ?
                    'Click the button below to link and unlink OAuth services from your account.'
                    :
                    'Click the button below to link your account to third party OAuth services for more ways of logging in.'
                }
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
