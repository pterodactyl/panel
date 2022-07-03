import React, { useState } from 'react';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import DisableTwoFactorModal from '@/components/dashboard/forms/DisableTwoFactorModal';
import SetupTOTPModal from '@/components/dashboard/forms/SetupTOTPModal';
import RecoveryTokensDialog from '@/components/dashboard/forms/RecoveryTokensDialog';

export default () => {
    const [tokens, setTokens] = useState<string[]>([]);
    const [visible, setVisible] = useState<'enable' | 'disable' | null>(null);
    const isEnabled = useStoreState((state: ApplicationStore) => state.user.data!.useTotp);

    const onTokens = (tokens: string[]) => {
        setTokens(tokens);
        setVisible(null);
    };

    return (
        <div>
            <SetupTOTPModal open={visible === 'enable'} onClose={() => setVisible(null)} onTokens={onTokens} />
            <RecoveryTokensDialog tokens={tokens} open={tokens.length > 0} onClose={() => setTokens([])} />
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
