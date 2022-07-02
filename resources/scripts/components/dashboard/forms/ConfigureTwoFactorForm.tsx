import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import SetupTOTPModal from '@/components/dashboard/forms/SetupTOTPModal';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';

export default () => {
    const [visible, setVisible] = useState<'enable' | 'disable' | null>(null);
    const isEnabled = useStoreState((state: ApplicationStore) => state.user.data!.useTotp);

    return (
        <div>
            <SetupTOTPModal open={visible === 'enable'} onClose={() => setVisible(null)} />
            <DisableTwoFactorModal visible={visible === 'disable'} onModalDismissed={() => setVisible(null)} />
            <p css={tw`text-sm`}>
                {isEnabled
                    ? 'Two-step verification is currently enabled on your account.'
                    : 'You do not currently have two-step verification enabled on your account. Click the button below to begin configuring it.'}
            </p>
            <div css={tw`mt-6`}>
                {isEnabled ? (
                    <Button.Danger onClick={() => setVisible('disable')}>Disable Two-Step</Button.Danger>
                ) : (
                    <Button onClick={() => setVisible('enable')}>Enable Two-Step</Button>
                )}
            </div>
        </div>
    );
};
