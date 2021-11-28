import React, { useState } from 'react';
import SetupOAuthModal from '@/components/dashboard/forms/SetupOAuthModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';

export default () => {
    const [ visible, setVisible ] = useState(false);
    const oauth = JSON.parse(useStoreState((state: State<ApplicationStore>) => state.user.data!.oauth));

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
                {oauth.length === 0 ?
                    'Click the button below to link your account to third party OAuth services for more ways of logging in.'
                    :
                    'Click the button below to link and unlink OAuth services from your account.'
                }
            </p>
            <div css={tw`mt-6`}>
                <Button
                    color={'green'}
                    isSecondary
                    onClick={() => setVisible(true)}
                >
                    { oauth.length === 0 ? 'Begin Setup' : 'Configure'}
                </Button>
            </div>
        </div>
    );
};
