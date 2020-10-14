import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SetupTwoFactorModal from '@/components/dashboard/forms/SetupTwoFactorModal';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data!);
    const [ visible, setVisible ] = useState(false);

    return user.useTotp ?
        <div>
            {visible &&
            <DisableTwoFactorModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p css={tw`text-sm`}>
                Two-factor authentication is currently enabled on your account.
            </p>
            <div css={tw`mt-6`}>
                <Button
                    color={'red'}
                    isSecondary
                    onClick={() => setVisible(true)}
                >
                    Disable
                </Button>
            </div>
        </div>
        :
        <div>
            {visible &&
            <SetupTwoFactorModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <p css={tw`text-sm`}>
                You do not currently have two-factor authentication enabled on your account. Click
                the button below to begin configuring it.
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
    ;
};
